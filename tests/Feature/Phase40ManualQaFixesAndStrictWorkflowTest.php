<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Models\AccessoryOrder;
use App\Models\AdminActivityLog;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\CustomerInstallmentPlan;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\ShowroomAssistantService;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class Phase40ManualQaFixesAndStrictWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: Category, 1: Product, 2: Product}
     */
    private function createFixtures(): array
    {
        $carCategory = Category::create([
            'name' => 'BMW Sedan Phase 40',
            'slug' => 'bmw-sedan-phase-40',
        ]);
        $accCategory = Category::create([
            'name' => 'Phụ kiện Phase 40',
            'slug' => 'phu-kien-phase-40',
        ]);

        $car = Product::factory()->for($carCategory)->create([
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 3229000000,
            'deposit_amount' => 50000000,
            'stock' => 4,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->for($accCategory)->create([
            'name' => 'Camera Hành Trình BMW ACE 3.0',
            'slug' => 'camera-hanh-trinh-bmw-ace-3',
            'type' => VehicleType::ACCESSORY,
            'price' => 14500000,
            'deposit_amount' => 0,
            'stock' => 8,
            'is_active' => true,
        ]);

        return [$carCategory, $car, $accessory];
    }

    public function test_ai_assistant_deduplicates_action_cards_and_prevents_empty_markdown_links(): void
    {
        $this->createFixtures();
        /** @var ShowroomAssistantService $service */
        $service = app(ShowroomAssistantService::class);

        // 1. Empty or whitespace Markdown links [Tên dịch vụ]() are repaired to verified URLs or **bold** text — never []()
        $rawAnswer = "Các gói chăm sóc chính hãng:\n- [Bảo dưỡng định kỳ tiêu chuẩn BMW]()\n- [Phủ Ceramic & Hiệu chỉnh bề mặt sơn] ()\n- [ Hạng mục tùy chỉnh không có link ]()\n- []()";
        $sanitized = $service->sanitizeAndRepairMarkdownLinks($rawAnswer);

        $this->assertStringNotContainsString(']()', $sanitized);
        $this->assertStringNotContainsString('] ()', $sanitized);
        $this->assertStringContainsString('[Bảo dưỡng định kỳ tiêu chuẩn BMW](/booking?type=maintenance)', $sanitized);
        $this->assertStringContainsString('[Phủ Ceramic & Hiệu chỉnh bề mặt sơn](/booking?type=maintenance)', $sanitized);
        $this->assertStringContainsString('**Hạng mục tùy chỉnh không có link**', $sanitized);

        // 2. suggestionsFor deduplicates by normalized label and (kind, url) — never repeating "Đặt lịch tư vấn"
        $refMethod = new ReflectionMethod(ShowroomAssistantService::class, 'suggestionsFor');
        $suggestions = $refMethod->invoke(
            $service,
            'Tôi muốn đặt lịch bảo dưỡng và tư vấn tại showroom',
            ['products' => [], 'current_product' => null],
            $sanitized,
            null,
        );

        $labels = array_map(static fn (array $item): string => mb_strtolower(trim((string) ($item['label'] ?? ''))), $suggestions);
        $this->assertSame(count($labels), count(array_unique($labels)), 'Suggestions must never repeat identical action card labels.');
        $this->assertLessThanOrEqual(1, count(array_filter($labels, static fn (string $l): bool => $l === 'đặt lịch tư vấn')));

        // 3. Home page AI widget renders JSON script config (zero @js in x-data), expand button, draggable launcher, and IME handlers
        $homeHtml = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('x-data="aiAssistantWidget($el)"', $homeHtml);
        $this->assertStringContainsString('data-ai-widget-config', $homeHtml);
        $this->assertStringContainsString('data-ai-expand', $homeHtml);
        $this->assertStringContainsString('@pointerdown="startLauncherDrag($event)"', $homeHtml);
        $this->assertStringContainsString('@compositionstart="isComposing = true"', $homeHtml);
    }

    public function test_service_emails_render_bmw_dark_luxury_hero_without_stretched_fallback_pixel(): void
    {
        $sales = User::factory()->sales()->create([
            'name' => 'Lê Minh Khôi',
            'email' => 'sales.khoi40@bmw.com',
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Phạm Quốc Bảo',
            'guest_phone' => '0901404001',
            'guest_email' => 'bao.pham40@customer.vn',
            'product_id' => null,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(2)->setTime(9, 0),
            'status' => AppointmentStatus::Confirmed,
            'meta_data' => [
                'customer_car_model' => 'BMW X5 xDrive40i',
                'service_package' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
            ],
        ]);

        $confirmHtml = (new AppointmentConfirmationMail(
            appointment: $serviceBooking,
            mode: AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
        ))->render();

        $this->assertStringContainsString('data-bmw-dark-luxury-hero="1"', $confirmHtml);
        $this->assertStringContainsString('BMW SERVICE EXCELLENCE', $confirmHtml);
        $this->assertStringContainsString('BMW X5 xDrive40i', $confirmHtml);
        $this->assertStringNotContainsString('bmw-showroom-badge.png', $confirmHtml);

        $reminderHtml = (new AppointmentReminderMail($serviceBooking))->render();
        $this->assertStringContainsString('data-bmw-dark-luxury-hero="1"', $reminderHtml);
        $this->assertStringContainsString('BMW SERVICE REMINDER', $reminderHtml);
        $this->assertStringNotContainsString('bắt buộc khi tham gia lái thử', $reminderHtml);
    }

    public function test_outdated_v1_link_fades_summary_blocks_post_with_403_or_422_and_admin_locks_pending_reschedule(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();

        $topAdmin = User::factory()->topSuperAdmin()->create([
            'name' => 'Nguyễn Quốc Thái',
            'email' => 'thai@bmw.com',
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'Trần Thanh Tùng',
            'guest_phone' => '0902404002',
            'guest_email' => 'tung.tran40@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => Carbon::parse('2026-12-02 09:00:00', 'Asia/Ho_Chi_Minh')->utc(),
            'status' => AppointmentStatus::Pending,
        ]);

        $v1ManageUrl = $appointment->temporaryManageUrl();
        $v1UpdateUrl = $appointment->temporaryManageUpdateUrl();

        // Customer reschedules -> v=2, reschedule_pending_review = true
        $newSlot = Carbon::parse('2026-12-04 14:30:00', 'Asia/Ho_Chi_Minh')->format('Y-m-d H:i');
        $this->post($v1UpdateUrl, [
            'action' => 'reschedule',
            'new_appointment_date' => $newSlot,
            'reason' => 'Xin chuyển lịch sang chiều thứ Sáu.',
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame(2, $appointment->currentManageVersion());
        $this->assertTrue($appointment->hasPendingRescheduleReview());

        // 1. Outdated ?v=1 GET dims summary card (opacity-50 select-none), does NOT leak v=2 signed URL, and POST returns 403 / 422
        $v1Html = $this->get($v1ManageUrl)->assertOk()->getContent();
        $this->assertStringContainsString('data-outdated-link-banner', $v1Html);
        $this->assertStringContainsString('opacity-50 select-none', $v1Html);
        $this->assertStringContainsString('opacity-50 pointer-events-none select-none', $v1Html);
        $this->assertStringNotContainsString('v=2', $v1Html);

        $forbiddenResponse = $this->post($v1UpdateUrl, ['action' => 'confirm']);
        $forbiddenResponse->assertForbidden();
        $this->assertStringNotContainsString('v=2', $forbiddenResponse->getContent());

        $this->postJson($v1UpdateUrl, ['action' => 'cancel'])
            ->assertStatus(422)
            ->assertJsonPath('errors.link.0', fn (string $msg): bool => str_contains($msg, 'phiên bản cũ'));

        // 2. Admin CRM renders old schedule struck-through (opacity-50 line-through), locks Section 4, and blocks backend progression
        $drawerTest = Livewire::actingAs($topAdmin)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('opacity-50 line-through', false)
            ->assertSee('data-reschedule-locked="true"', false)
            ->assertSee('reschedule-action-lock-appointment-'.$appointment->id);

        // Backend hard-blocks Confirmed & Completed while reschedule_pending_review === true
        $drawerTest->call('updateStatus', $appointment->id, AppointmentStatus::Confirmed->value)
            ->assertHasErrors('statusTransition');
        $drawerTest->call('openCompletionModal', $appointment->id)
            ->assertHasErrors('statusTransition');
        $drawerTest->call('resendConfirmationEmail', $appointment->id)
            ->assertHasErrors('statusTransition');

        $this->expectException(InvalidStatusTransitionException::class);
        $appointment->fresh()->transitionStatusWithAutomation(AppointmentStatus::Confirmed, $topAdmin);
    }

    public function test_acknowledge_reschedule_syncs_pending_to_confirmed_and_prevents_duplicate_confirmation_emails(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();

        $sales = User::factory()->sales()->create([
            'name' => 'Võ Minh Trí',
            'email' => 'sales.tri40@bmw.com',
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'Hoàng Nhật Minh',
            'guest_phone' => '0903404003',
            'guest_email' => 'minh.hoang40@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'status' => AppointmentStatus::Pending,
            'meta_data' => [
                'manage_version' => 2,
                'reschedule_pending_review' => true,
                'previous_appointment_date' => '08:30 — 28/11/2026',
            ],
        ]);

        // Acknowledge reschedule on Pending record -> auto-transitions Pending -> Confirmed & sends exactly 1 official confirmation mail (v=2)
        $component = Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertFalse($appointment->hasPendingRescheduleReview());

        Mail::assertQueued(AppointmentConfirmationMail::class, function (AppointmentConfirmationMail $mail) use ($appointment): bool {
            return $mail->appointment->id === $appointment->id
                && $mail->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED;
        });
        $adminConfirmedCount = count(Mail::queued(AppointmentConfirmationMail::class, fn (AppointmentConfirmationMail $m): bool => $m->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED));
        $this->assertSame(1, $adminConfirmedCount);

        // Calling automatic sendOfficialConfirmationEmail again for the same manage_version is a strict no-op (zero duplicates)
        $this->assertFalse($appointment->sendOfficialConfirmationEmail($sales, false));
        $this->assertSame(1, count(Mail::queued(AppointmentConfirmationMail::class, fn (AppointmentConfirmationMail $m): bool => $m->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED)));

        // Manual resend works once when Confirmed clean, then locks for 30s against double-clicks
        $component->call('resendConfirmationEmail', $appointment->id)->assertHasNoErrors();
        $this->assertSame(2, count(Mail::queued(AppointmentConfirmationMail::class, fn (AppointmentConfirmationMail $m): bool => $m->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED)));

        $component->call('resendConfirmationEmail', $appointment->id)->assertHasErrors('statusTransition');
        $this->assertSame(2, count(Mail::queued(AppointmentConfirmationMail::class, fn (AppointmentConfirmationMail $m): bool => $m->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED)));
    }

    public function test_three_branch_completion_modals_thank_you_emails_and_timeline_actor_attribution(): void
    {
        Mail::fake();
        [, $car] = $this->createFixtures();

        Carbon::setTestNow(Carbon::parse('2026-11-27 15:00:00', 'Asia/Ho_Chi_Minh'));

        $sales = User::factory()->sales()->create([
            'name' => 'Đặng Thu Hà',
            'email' => 'sales.ha40@bmw.com',
        ]);

        // 1. Customer self-confirm records distinct customer timeline event (never "Hoàn tất")
        $selfConfirmAppt = Appointment::create([
            'guest_name' => 'Lương Thế Vinh',
            'guest_phone' => '0904404004',
            'guest_email' => 'vinh.luong40@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TradeIn,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->post($selfConfirmAppt->temporaryManageUpdateUrl(), ['action' => 'confirm'])
            ->assertRedirect();

        $selfConfirmAppt->refresh();
        $timelineAfterSelfConfirm = InteractionTimelineRecorder::timelineForAppointment($selfConfirmAppt);
        $titles = array_column($timelineAfterSelfConfirm, 'title');
        $this->assertContains('Khách hàng đã xác nhận sẽ đến lịch hẹn', $titles);
        foreach ($titles as $t) {
            $this->assertStringNotContainsString('Hoàn tất', $t);
        }

        // Direct updateStatus(..., 'completed') without modal outcome is strictly rejected!
        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('updateStatus', $selfConfirmAppt->id, AppointmentStatus::Completed->value)
            ->assertHasErrors('statusTransition');
        $this->assertSame(AppointmentStatus::Confirmed, $selfConfirmAppt->fresh()->status);

        // Complete Trade-in via Trade-in Completion Modal -> sends Trade-in email & logs exact Staff Name + Role + Timestamp
        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('openCompletionModal', $selfConfirmAppt->id)
            ->assertSet('completionTargetCategory', 'trade_in')
            ->call('selectCompletionOutcome', Appointment::OUTCOME_TRADE_IN_CLOSED)
            ->set('completionNote', 'Định giá BMW 320i 2021 đạt 1,28 tỷ VNĐ.')
            ->call('confirmCompletion')
            ->assertHasNoErrors();

        $selfConfirmAppt->refresh();
        $this->assertSame(AppointmentStatus::Completed, $selfConfirmAppt->status);
        $this->assertStringContainsString('Đặng Thu Hà (Tư Vấn Bán Hàng)', (string) $selfConfirmAppt->completedAttributionLabel());

        $activityLog = AdminActivityLog::query()->latest('id')->first();
        $this->assertNotNull($activityLog);
        $this->assertStringContainsString('Nhân sự Đặng Thu Hà (Tư Vấn Bán Hàng) đã xác nhận HOÀN TẤT', $activityLog->description);

        // 2. Service Booking Completion Modal -> dedicated BMW Service Thank-You Email (NO new-car, test-drive, or BMW-CARE purchase voucher; NO PostTestDriveFollowupMail)
        $serviceBooking = Appointment::create([
            'guest_name' => 'Bùi Anh Tuấn',
            'guest_phone' => '0905404005',
            'guest_email' => 'tuan.bui40@customer.vn',
            'product_id' => null,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::Detailing,
            'appointment_date' => now()->addDay()->setTime(14, 0),
            'status' => AppointmentStatus::Pending,
            'meta_data' => [
                'customer_car_model' => 'BMW 735i Pure Excellence',
                'service_package' => 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)',
            ],
        ]);

        Livewire::actingAs($sales)
            ->test(ServiceBookingTable::class)
            ->call('updateStatus', $serviceBooking->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors()
            // Skipping modal directly is blocked
            ->call('updateStatus', $serviceBooking->id, AppointmentStatus::Completed->value)
            ->assertHasErrors('statusTransition')
            // Proper Service Completion Modal flow
            ->call('openCompletionModal', $serviceBooking->id)
            ->assertSee('data-service-completion-modal="'.$serviceBooking->id.'"', false)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_SERVICE_DELIVERED)
            ->set('serviceCompletionNote', 'Hiệu chỉnh sơn 3 bước & phủ Ceramic 9H chính hãng.')
            ->set('serviceActualCostInput', '18.500.000')
            ->set('serviceReminderMonths', 6)
            ->call('confirmCompletion')
            ->assertHasNoErrors();

        $serviceBooking->refresh();
        $this->assertSame(AppointmentStatus::Completed, $serviceBooking->status);
        $this->assertNull($serviceBooking->careVoucherCode());
        $this->assertSame(18500000, $serviceBooking->meta_data['service_actual_cost']);

        Mail::assertQueued(AppointmentCompletedThankYouMail::class, function (AppointmentCompletedThankYouMail $mail) use ($serviceBooking): bool {
            if ($mail->appointment->id !== $serviceBooking->id) {
                return false;
            }

            $html = $mail->render();
            $this->assertStringContainsString('Xưởng Dịch vụ Chính hãng BMW', $html);
            $this->assertStringContainsString('18.500.000 VNĐ', $html);
            $this->assertStringContainsString('Sau 6 tháng', $html);
            $this->assertStringNotContainsString('BMW-CARE-', $html);
            $this->assertStringNotContainsString('gia nhập cộng đồng chủ nhân BMW', $html);
            $this->assertStringNotContainsString('lái thử', mb_strtolower($html));

            return true;
        });
        Mail::assertNotQueued(PostTestDriveFollowupMail::class);

        // Duplicate completion email call is idempotently ignored
        $this->assertFalse($serviceBooking->sendCompletedThankYouEmail(Appointment::OUTCOME_SERVICE_DELIVERED));

        Carbon::setTestNow();
    }

    public function test_installment_form_renders_live_calculator_preserves_diacritics_and_maintains_8_query_budget(): void
    {
        [, $car, $accessory] = $this->createFixtures();

        $topAdmin = User::factory()->topSuperAdmin()->create([
            'name' => 'ĐẶNG QUANG HUY',
            'email' => 'thai@bmw.com',
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'ĐẶNG QUANG HUY',
            'guest_phone' => '0906404006',
            'guest_email' => 'huy.dang40@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Appointment::create([
            'guest_name' => 'Khách Dịch Vụ 40',
            'guest_phone' => '0907404007',
            'guest_email' => 'srv40@customer.vn',
            'type' => AppointmentType::CarWash,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
        ]);

        AccessoryOrder::create([
            'customer_name' => 'Khách Phụ Kiện 40',
            'customer_phone' => '0908404008',
            'customer_email' => 'acc40@customer.vn',
            'customer_address' => '88 Nguyễn Huệ, Q.1',
            'product_id' => $accessory->id,
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        // 1. /admin/customers renders ĐẶNG QUANG HUY with leading-snug py-0.5 (never leading-none that clips Ặ)
        $customersHtml = $this->actingAs($topAdmin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString('ĐẶNG QUANG HUY', $customersHtml);
        $this->assertStringContainsString('leading-snug', $customersHtml);
        $this->assertStringNotContainsString('uppercase leading-none', $customersHtml);

        // 2. Store installment plan with clean integer values & inspect Customer 360° Live Calculator markup
        $this->actingAs($topAdmin)
            ->post(route('admin.customers.installments.store', ['phone' => '0906404006']), [
                'route_email' => 'huy.dang40@customer.vn',
                'bank_name' => 'Techcombank BMW Financial',
                'product_id' => $car->id,
                'appointment_id' => $appointment->id,
                'vehicle_price' => 3229000000,
                'down_payment' => 1229000000,
                'loan_amount' => 2000000000,
                'monthly_payment' => 46555000,
                'interest_rate' => 7.9,
                'interest_method' => CustomerInstallmentPlan::METHOD_REDUCING_BALANCE,
                'term_months' => 60,
                'paid_installments' => 2,
                'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
            ])
            ->assertRedirect();

        $plan = CustomerInstallmentPlan::query()->where('customer_phone', '0906404006')->firstOrFail();
        $this->assertSame(46555000, (int) $plan->monthly_payment);

        $showHtml = $this->actingAs($topAdmin)
            ->get(route('admin.customers.show', ['phone' => '0906404006', 'email' => 'huy.dang40@customer.vn']))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-data="bmwInstallmentCalculator($el)"', $showHtml);
        $this->assertStringContainsString('data-installment-calc-config', $showHtml);
        $this->assertStringContainsString('data-installment-live-preview', $showHtml);
        $this->assertStringContainsString('46555000', $showHtml);

        // 3. Cold-cache <= 8 SQL queries budget on all 3 CRM tables
        $this->actingAs($topAdmin);

        foreach ([AppointmentTable::class, ServiceBookingTable::class, AccessoryOrderTable::class] as $tableClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test($tableClass)->assertOk();
            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queryCount, "{$tableClass} exceeded <= 8 cold-cache SQL query budget (actual: {$queryCount}).");
        }
    }
}
