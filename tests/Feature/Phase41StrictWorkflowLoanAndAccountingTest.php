<?php

namespace Tests\Feature;

use App\Collections\AppointmentCollection;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\CustomerInstallmentPlan;
use App\Models\Product;
use App\Models\User;
use App\Services\Finance\InstallmentScheduleCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase41StrictWorkflowLoanAndAccountingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Category, 1: Product, 2: Product}
     */
    private function createFixtures(): array
    {
        $category = Category::query()->create([
            'name' => 'BMW Sedan Series',
            'slug' => 'bmw-sedan-series-41',
            'description' => 'Showroom Phase 41 vehicles',
        ]);

        $car = Product::factory()->for($category)->create([
            'type' => VehicleType::CAR,
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport-41',
            'price' => 3200000000,
            'deposit_amount' => 50000000,
            'stock' => 4,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->for($category)->create([
            'type' => VehicleType::ACCESSORY,
            'name' => 'Thảm sàn BMW M Performance',
            'slug' => 'tham-san-bmw-41',
            'price' => 5000000,
            'deposit_amount' => 0,
            'stock' => 20,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    public function test_second_reschedule_from_confirmed_reverts_to_pending_and_acknowledging_dispatches_single_vip_pass(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();
        $sales = User::factory()->sales()->create(['name' => 'Trần Quốc Bảo', 'email' => 'bao.sales41@bmw.com']);

        $appointment = Appointment::query()->create([
            'guest_name' => 'Nguyễn Gia Huy',
            'guest_phone' => '0908414101',
            'guest_email' => 'giahuy.41@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(9, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        // 1. Staff confirms initially (v=1)
        $appointment->transitionStatusWithAutomation(AppointmentStatus::Confirmed, $sales);
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Mail::assertQueued(AppointmentConfirmationMail::class, 1);

        // 2. Reschedule #1 from Confirmed (unconfirmed guest attendance) -> reverts to Pending (v=2)
        $slot2 = now('Asia/Ho_Chi_Minh')->addDays(3)->setTime(14, 0);
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $slot2->format('Y-m-d'),
            'new_time' => $slot2->format('H:i'),
            'reason' => 'Dời lịch lần 1 để tiện công việc.',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertSame(2, $appointment->currentManageVersion());
        $this->assertSame(1, $appointment->rescheduleCount());
        $this->assertTrue($appointment->hasPendingRescheduleReview());

        // Staff acknowledges reschedule #1 -> Confirmed + sends v=2 VIP Pass
        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertFalse($appointment->hasPendingRescheduleReview());
        Mail::assertQueued(AppointmentConfirmationMail::class, 2);

        // 3. Reschedule #2 from Confirmed -> MUST revert to Pending, increment reschedule_count=2, store previous_status=confirmed, and cross-out Confirmed in Email & CRM!
        $slot3 = now('Asia/Ho_Chi_Minh')->addDays(4)->setTime(16, 0);
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $slot3->format('Y-m-d'),
            'new_time' => $slot3->format('H:i'),
            'reason' => 'Xin dời thêm lần 2 sang 16:00.',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertSame(3, $appointment->currentManageVersion());
        $this->assertSame(2, $appointment->rescheduleCount());
        $this->assertTrue($appointment->hasPendingRescheduleReview());
        $this->assertSame('Đã xác nhận', $appointment->previousRescheduleStatusLabel());

        Mail::assertQueued(GuestAppointmentChangeMail::class, function (GuestAppointmentChangeMail $mail): bool {
            $html = $mail->render();
            $this->assertStringContainsString('#1C69D4', $html);
            $this->assertStringContainsString('text-decoration: line-through;', $html);
            $this->assertStringContainsString('Đã xác nhận', $html);
            $this->assertStringContainsString('Chờ xác nhận', $html);

            return true;
        });

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('Chờ duyệt dời lịch lần 2')
            ->assertSee('data-reschedule-locked="true"', false)
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertFalse($appointment->hasPendingRescheduleReview());
        Mail::assertQueued(AppointmentConfirmationMail::class, 3);
    }

    public function test_guest_attendance_confirm_decouples_from_official_confirm_suppresses_redundant_mail_and_locks_changes(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();
        $sales = User::factory()->sales()->create(['name' => 'Lê Minh Khôi', 'email' => 'khoi.sales41@bmw.com']);

        $appointment = Appointment::query()->create([
            'guest_name' => 'Phạm Thùy Dương',
            'guest_phone' => '0908414102',
            'guest_email' => 'thuyduong.41@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(10, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        // Guest clicks "Xác nhận chắc chắn đến Showroom" while Pending
        $this->post($appointment->temporaryManageUpdateUrl(), ['action' => 'confirm'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertTrue($appointment->hasGuestAttendanceConfirmed());

        // Zero redundant GuestAppointmentChangeMail (08:00 -> 08:00) or VIP Pass before staff approves
        Mail::assertNotSent(GuestAppointmentChangeMail::class);
        Mail::assertNothingQueued();

        // Guest manage page renders light theme & locked reschedule/cancel cards with Hotline guidance
        $this->get($appointment->temporaryManageUrl())
            ->assertOk()
            ->assertSee('data-guest-confirmed-change-lock', false)
            ->assertSee('data-picker-theme="light"', false)
            ->assertSee('bmw-light-scroll', false)
            ->assertSee('opacity-50 pointer-events-none select-none', false);

        // Backend hard-blocks reschedule & cancel after guest_confirmed_at
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => now('Asia/Ho_Chi_Minh')->addDays(4)->format('Y-m-d'),
            'new_time' => '15:00',
        ])->assertForbidden();

        $this->postJson($appointment->temporaryManageUpdateUrl(), [
            'action' => 'cancel',
        ])->assertStatus(422);

        // Staff sees badge in CRM & approves Pending -> Confirmed, sending 1 official VIP Pass
        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->assertSee('Khách đã xác nhận tham dự')
            ->call('updateStatus', $appointment->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors();

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        Mail::assertQueued(AppointmentConfirmationMail::class, 1);

        // Clicking guest confirm again AFTER VIP Pass already sent for current version does NOT send duplicate VIP Pass
        $this->post($appointment->temporaryManageUpdateUrl(), ['action' => 'confirm'])->assertRedirect();
        Mail::assertQueued(AppointmentConfirmationMail::class, 1);
    }

    public function test_terminal_rows_dim_across_crm_copy_log_exists_and_lookup_hides_ai_widget(): void
    {
        [, $car, $accessory] = $this->createFixtures();
        $topAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);

        $completedAppt = Appointment::query()->create([
            'guest_name' => 'Khách Terminal Sales',
            'guest_phone' => '0908414103',
            'guest_email' => 'terminal.sales@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_VISITED_NO_PURCHASE],
        ]);

        $completedService = Appointment::query()->create([
            'guest_name' => 'Khách Terminal Service',
            'guest_phone' => '0908414104',
            'guest_email' => 'terminal.service@customer.vn',
            'type' => AppointmentType::Detailing,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_SERVICE_DELIVERED, 'service_actual_cost' => 3500000],
        ]);

        $completedOrder = AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Terminal Order',
            'customer_phone' => '0908414105',
            'customer_email' => 'terminal.order@customer.vn',
            'customer_address' => '123 Lê Lợi',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_COMPLETED,
        ]);

        Livewire::actingAs($topAdmin)
            ->test(AppointmentTable::class)
            ->assertSee('data-terminal-row="true"', false)
            ->assertSee('opacity-55', false)
            ->call('inspectAppointment', $completedAppt->id)
            ->assertSee('data-copy-debug-log-btn', false);

        Livewire::actingAs($topAdmin)
            ->test(ServiceBookingTable::class)
            ->assertSee('data-terminal-row="true"', false)
            ->assertSee('opacity-55', false)
            ->call('inspectBooking', $completedService->id)
            ->assertSee('data-copy-debug-log-btn', false);

        Livewire::actingAs($topAdmin)
            ->test(AccessoryOrderTable::class)
            ->assertSee('data-terminal-row="true"', false)
            ->assertSee('opacity-55', false)
            ->call('inspectOrder', $completedOrder->id)
            ->assertSee('data-copy-debug-log-btn', false);

        // /admin/users includes activity log copy controls
        $this->actingAs($topAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('data-copy-all-activity-logs', false);

        // /tra-cuu does NOT render AI assistant widget
        $this->get(route('customer.lookup.index'))
            ->assertOk()
            ->assertDontSee('data-ai-assistant-widget', false);

        $this->post(route('customer.lookup.search'), ['query' => '0908414103'])
            ->assertOk()
            ->assertDontSee('data-ai-assistant-widget', false);
    }

    public function test_installment_auto_draft_on_purchase_purchased_only_guard_pricing_api_and_two_stage_calculator(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();
        $sales = User::factory()->sales()->create(['name' => 'Hoàng Minh Đức', 'email' => 'duc.sales41@bmw.com']);

        // 1. Customer with visited_no_purchase CANNOT attach any vehicle to installments
        $visitedAppt = Appointment::query()->create([
            'guest_name' => 'Khách Chưa Mua Xe',
            'guest_phone' => '0908414106',
            'guest_email' => 'chuamua@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_VISITED_NO_PURCHASE],
        ]);

        $this->actingAs($sales)
            ->get(route('admin.customers.show', ['phone' => '0908414106']))
            ->assertOk()
            ->assertSee('data-no-purchased-vehicle-banner', false);

        $this->actingAs($sales)
            ->post(route('admin.customers.installments.store', ['phone' => '0908414106']), [
                'bank_name' => 'Techcombank',
                'product_id' => $car->id,
                'loan_amount' => 2000000000,
                'monthly_payment' => 42000000,
                'interest_rate' => 7.9,
                'term_months' => 60,
                'paid_installments' => 0,
                'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('product_id');

        // 2. Pricing API requires CRM access and returns numeric vehicle price
        auth()->guard('web')->logout();
        $this->getJson(route('admin.api.products.pricing', $car))->assertUnauthorized();
        $customerUser = User::factory()->create();
        $this->actingAs($customerUser)->getJson(route('admin.api.products.pricing', $car))->assertForbidden();
        $this->actingAs($sales)
            ->getJson(route('admin.api.products.pricing', $car))
            ->assertOk()
            ->assertJson([
                'id' => $car->id,
                'price' => 3200000000,
            ]);

        // 3. Completing Confirmed sale with payment_method='installment' auto-creates 1 DRAFT plan & sends loan-free thank-you mail
        $confirmedSale = Appointment::query()->create([
            'guest_name' => 'Vũ Hoàng Anh',
            'guest_phone' => '0908414107',
            'guest_email' => 'hoanganh.buy41@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subHours(2),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('openCompletionModal', $confirmedSale->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_PURCHASED)
            ->set('completionPaymentMethod', 'installment')
            ->set('completionFinalPriceInput', '3.100.000.000')
            ->set('completionDownPaymentInput', '900.000.000')
            ->set('completionBankName', 'Techcombank BMW Financial')
            ->set('completionTermMonths', 60)
            ->call('confirmCompletion')
            ->assertHasNoErrors();

        $draftPlan = CustomerInstallmentPlan::query()->where('appointment_id', $confirmedSale->id)->firstOrFail();
        $this->assertSame(1, CustomerInstallmentPlan::query()->where('appointment_id', $confirmedSale->id)->count());
        $this->assertSame(CustomerInstallmentPlan::STATUS_DRAFT, $draftPlan->status);
        $this->assertSame(3100000000, $draftPlan->vehicle_price);
        $this->assertSame(900000000, $draftPlan->down_payment);
        $this->assertSame(2200000000, $draftPlan->loan_amount);
        $this->assertSame(12, $draftPlan->fixed_rate_months);
        $this->assertSame(3, $draftPlan->floating_rate_adjustment_months);

        // Congratulatory email MUST NOT mention installment/loan/debt keywords!
        Mail::assertQueued(AppointmentCompletedThankYouMail::class, function (AppointmentCompletedThankYouMail $mail): bool {
            $html = mb_strtolower($mail->render());
            $this->assertStringNotContainsString('trả góp', $html);
            $this->assertStringNotContainsString('vay ngân hàng', $html);
            $this->assertStringNotContainsString('dư nợ', $html);
            $this->assertStringNotContainsString('lãi suất', $html);

            return true;
        });

        // 4. Draft plan is NOT shown on /tra-cuu until Sales activates it & configures floating rate
        $this->post(route('customer.lookup.search'), ['query' => '0908414107'])
            ->assertOk()
            ->assertDontSee('data-installment-plan-card', false);

        $this->actingAs($sales)
            ->patch(route('admin.customers.installments.update', ['phone' => '0908414107', 'plan' => $draftPlan->id]), [
                'bank_name' => 'Techcombank BMW Financial',
                'loan_amount' => 2200000000,
                'monthly_payment' => 51000000,
                'interest_rate' => 7.5,
                'fixed_rate_months' => 12,
                'floating_rate_adjustment_months' => 6,
                'floating_interest_rate' => 10.5,
                'early_settlement_fee_note' => 'Năm 1-2: 1.5%, Từ năm 3: Miễn phí',
                'term_months' => 60,
                'paid_installments' => 0,
                'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $draftPlan->refresh();
        $calc = app(InstallmentScheduleCalculator::class)->calculate($draftPlan);
        $this->assertSame(7.5, $calc['schedule'][0]['interest_rate_applied']);
        $this->assertSame('fixed', $calc['schedule'][0]['rate_phase']);
        $this->assertSame(10.5, $calc['schedule'][12]['interest_rate_applied']);
        $this->assertSame('floating', $calc['schedule'][12]['rate_phase']);

        $this->post(route('customer.lookup.search'), ['query' => '0908414107'])
            ->assertOk()
            ->assertSee('data-installment-plan-card', false)
            ->assertSee('Điều chỉnh mỗi 6 tháng / lần')
            ->assertSee('Năm 1-2: 1.5%, Từ năm 3: Miễn phí');
    }

    public function test_accounting_eliminates_fake_deposits_and_tracks_real_vehicle_service_and_accessory_revenue(): void
    {
        [, $car, $accessory] = $this->createFixtures();
        $topAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);

        // Confirmed test-drive with NO real deposit -> MUST contribute 0đ (never fake 50M/100M)
        $confirmedTestDrive = Appointment::query()->create([
            'guest_name' => 'Khách Lái Thử Không Cọc',
            'guest_phone' => '0908414111',
            'guest_email' => 'nocọc@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        // Completed visit without buying -> MUST contribute 0đ to revenue and deposits
        Appointment::query()->create([
            'guest_name' => 'Khách Tham Quan Không Mua',
            'guest_phone' => '0908414112',
            'guest_email' => 'visitedonly@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_VISITED_NO_PURCHASE],
        ]);

        // Completed vehicle purchase (installment, negotiated final price = 3.100.000.000) -> 3.100.000.000đ revenue
        Appointment::query()->create([
            'guest_name' => 'Khách Đã Mua Xe Trả Góp',
            'guest_phone' => '0908414113',
            'guest_email' => 'purchased41@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->subHours(5),
            'status' => AppointmentStatus::Completed,
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
                'payment_method' => 'installment',
                'final_vehicle_price' => 3100000000,
                'down_payment_amount' => 1000000000,
                'financed_loan_amount' => 2100000000,
            ],
        ]);

        // Completed service booking with actual cost = 4.500.000đ -> 4.500.000đ revenue
        Appointment::query()->create([
            'guest_name' => 'Khách Bảo Dưỡng',
            'guest_phone' => '0908414114',
            'guest_email' => 'service41@customer.vn',
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->subHours(4),
            'status' => AppointmentStatus::Completed,
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_SERVICE_DELIVERED,
                'service_actual_cost' => 4500000,
            ],
        ]);

        // Completed accessory order -> 5.000.000đ revenue
        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện',
            'customer_phone' => '0908414115',
            'customer_email' => 'acc41@customer.vn',
            'customer_address' => '123 Nguyễn Văn Linh',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_COMPLETED,
        ]);

        $collection = Appointment::query()->with('product:id,price,deposit_amount')->get();
        $this->assertSame(0, AppointmentCollection::resolveDepositAmount($confirmedTestDrive));
        $this->assertSame(0, $collection->committedDepositValue());
        $this->assertSame(3100000000, $collection->realizedVehicleRevenue());
        $this->assertSame(4500000, $collection->realizedServiceRevenue());

        $todayDate = now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        $this->actingAs($topAdmin)
            ->get(route('admin.accounting.index'))
            ->assertOk()
            ->assertSee('3.109.500.000 ₫');

        $this->actingAs($topAdmin)
            ->get(route('admin.accounting.day', ['date' => $todayDate]))
            ->assertOk()
            ->assertSee('3.109.500.000 ₫')
            ->assertSee('Đối ứng 1.000.000.000 ₫')
            ->assertDontSee('50.000.000 ₫');

        $this->actingAs($topAdmin)
            ->post(route('admin.accounting.close-day', ['date' => $todayDate]), ['notes' => 'Chốt chuẩn Phase 41'])
            ->assertRedirect();

        $this->assertDatabaseHas('accounting_daily_closings', [
            'completed_revenue' => 3109500000,
            'committed_value' => 0,
            'cancelled_value' => 0,
        ]);

        // Preserve <= 8 cold-cache SQL query budget across all 3 CRM Livewire components
        $this->actingAs($topAdmin);
        foreach ([AppointmentTable::class, ServiceBookingTable::class, AccessoryOrderTable::class] as $componentClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test($componentClass)->assertOk();
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queries, "{$componentClass} exceeded 8 cold-cache SQL query budget ({$queries}).");
        }
    }
}
