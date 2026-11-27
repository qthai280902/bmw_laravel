<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Exceptions\InvalidStatusTransitionException;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase31CrmStateMachineAndActionHubTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(): User
    {
        return User::factory()->create([
            'name' => 'Lê Minh Quản Lý',
            'email' => uniqid('crm_admin_', true).'@bmw.com',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function createFixtures(): array
    {
        $category = Category::factory()->create([
            'name' => 'BMW M Series',
            'slug' => 'bmw-m-series-'.uniqid(),
        ]);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW M340i xDrive',
            'slug' => 'bmw-m340i-xdrive-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 3499000000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Nẹp Bậc Cửa Phát Sáng BMW',
            'slug' => 'nep-bac-cua-bmw-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 6800000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$car, $accessory];
    }

    public function test_drawer_action_hub_strictly_matches_current_status_across_all_three_crm_tables(): void
    {
        Mail::fake();

        $admin = $this->createStaff();
        [$car, $accessory] = $this->createFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Trần Quốc Hưng',
            'guest_phone' => '0908111222',
            'guest_email' => 'hung.tran@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Võ Tấn Đạt',
            'guest_phone' => '0908333444',
            'guest_email' => 'dat.vo@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'meta_data' => ['service_package' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)'],
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Đỗ Thúy An',
            'customer_phone' => '0908555666',
            'customer_email' => 'an.do@example.com',
            'customer_address' => '25 Đồng Khởi, Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        // 1. AppointmentTable lifecycle: Pending -> Confirmed -> Completed (and lock)
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('4. Thao tác xử lý hồ sơ')
            ->assertSee('action-confirm-appointment-'.$appointment->id)
            ->assertSee('action-cancel-appointment-'.$appointment->id)
            ->assertDontSee('action-complete-appointment-'.$appointment->id)
            ->assertDontSee('terminal-status-lock-appointment-'.$appointment->id)
            ->assertSee('5. Xem trước mẫu Email (Chỉ xem — Không gửi thư)')
            ->assertSee('Xem thư Hệ thống tiếp nhận')
            ->assertSee('Xem thư Admin duyệt VIP')
            ->assertSee('Xem thư Khách đổi lịch')
            ->assertSee('Xem thư Nhắc lịch 24h')
            ->assertSee('Xem thư Hậu mãi trả góp')
            ->call('updateStatus', $appointment->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors()
            ->assertDontSee('action-confirm-appointment-'.$appointment->id)
            ->assertSee('action-complete-appointment-'.$appointment->id)
            ->assertSee('action-cancel-appointment-'.$appointment->id)
            ->call('openCompletionModal', $appointment->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_VISITED_NO_PURCHASE)
            ->call('confirmCompletion')
            ->assertHasNoErrors()
            ->assertDontSee('action-confirm-appointment-'.$appointment->id)
            ->assertDontSee('action-complete-appointment-'.$appointment->id)
            ->assertDontSee('action-cancel-appointment-'.$appointment->id)
            ->assertSee('terminal-status-lock-appointment-'.$appointment->id)
            ->assertSee('Đã đóng · Hoàn tất');

        // Cancelled appointment also locks UI controls
        $cancelledAppointment = Appointment::create([
            'guest_name' => 'Ngô Hoàng Việt',
            'guest_phone' => '0908777888',
            'guest_email' => 'viet.ngo@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Quote,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $cancelledAppointment->id)
            ->call('updateStatus', $cancelledAppointment->id, AppointmentStatus::Cancelled->value)
            ->assertHasNoErrors()
            ->assertSee('terminal-status-lock-appointment-'.$cancelledAppointment->id)
            ->assertSee('Đã đóng · Đã hủy')
            ->assertDontSee('action-confirm-appointment-'.$cancelledAppointment->id)
            ->assertDontSee('action-complete-appointment-'.$cancelledAppointment->id);

        // 2. ServiceBookingTable lifecycle + Read-Only Preview excludes "Hậu mãi trả góp"
        Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->call('inspectBooking', $serviceBooking->id)
            ->assertSee('action-confirm-service-booking-'.$serviceBooking->id)
            ->assertSee('action-cancel-service-booking-'.$serviceBooking->id)
            ->assertDontSee('action-complete-service-booking-'.$serviceBooking->id)
            ->assertSee('5. Xem trước mẫu Email (Chỉ xem — Không gửi thư)')
            ->assertSee('Xem thư Hệ thống tiếp nhận')
            ->assertSee('Xác nhận xưởng dịch vụ')
            ->assertSee('Xem thư Khách đổi lịch')
            ->assertSee('Xem thư Nhắc lịch 24h')
            ->assertDontSee('Xem thư Hậu mãi trả góp')
            ->call('updateStatus', $serviceBooking->id, AppointmentStatus::Confirmed->value)
            ->assertSee('action-complete-service-booking-'.$serviceBooking->id)
            ->call('openCompletionModal', $serviceBooking->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_SERVICE_DELIVERED)
            ->call('confirmCompletion')
            ->assertSee('terminal-status-lock-service-booking-'.$serviceBooking->id);

        // 3. AccessoryOrderTable lifecycle + Read-Only Preview shows ONLY Accessory order templates
        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('inspectOrder', $order->id)
            ->assertSee('action-confirm-accessory-order-'.$order->id)
            ->assertSee('action-cancel-accessory-order-'.$order->id)
            ->assertDontSee('action-complete-accessory-order-'.$order->id)
            ->assertSee('5. Xem trước mẫu Email (Chỉ xem — Không gửi thư)')
            ->assertSee('Thư xác nhận đặt phụ kiện')
            ->assertSee('Thư cập nhật trạng thái đơn')
            ->assertDontSee('Xem thư Hậu mãi trả góp')
            ->assertDontSee('Xem thư Nhắc lịch 24h')
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CONFIRMED)
            ->assertSee('action-complete-accessory-order-'.$order->id)
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_COMPLETED)
            ->assertSee('terminal-status-lock-accessory-order-'.$order->id);

        // Verify admin.mail-preview.accessory-order renders both modes with Clean Minimalist Luxury light theme
        foreach (['confirmation', 'status'] as $mode) {
            $preview = $this->actingAs($admin)->get(route('admin.mail-preview.accessory-order', [
                'accessoryOrder' => $order,
                'mode' => $mode,
            ]));
            $preview->assertOk();
            $preview->assertSee('#F1F5F9', false);
            $preview->assertSee('Nẹp Bậc Cửa Phát Sáng BMW');
        }
    }

    public function test_backend_rejects_skipping_or_reversing_state_machine_transitions_without_side_effects(): void
    {
        Mail::fake();

        $admin = $this->createStaff();
        [$car, $accessory] = $this->createFixtures();

        $pendingApp = Appointment::create([
            'guest_name' => 'Lâm Gia Minh',
            'guest_phone' => '0911000111',
            'guest_email' => 'minh.lam@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        // Direct model guard: Pending -> Completed is blocked
        try {
            $pendingApp->transitionStatusWithAutomation(AppointmentStatus::Completed, $admin);
            $this->fail('Expected InvalidStatusTransitionException when skipping Confirmed.');
        } catch (InvalidStatusTransitionException $exception) {
            $this->assertStringContainsString('Chờ xác nhận', $exception->getMessage());
        }
        $this->assertSame(AppointmentStatus::Pending, $pendingApp->fresh()->status);

        // Livewire AppointmentTable rejects Pending -> Completed and shows errorMessage
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('updateStatus', $pendingApp->id, AppointmentStatus::Completed->value)
            ->assertHasErrors('statusTransition')
            ->assertSee('Không thể chuyển trạng thái');
        $this->assertSame(AppointmentStatus::Pending, $pendingApp->fresh()->status);

        // HTTP Controller also blocks Pending -> Completed
        $this->actingAs($admin)
            ->patch(route('admin.appointments.update', $pendingApp), [
                'status' => AppointmentStatus::Completed->value,
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(AppointmentStatus::Pending, $pendingApp->fresh()->status);

        // Completed Appointment rejects backward/cancel transitions AND blocks resendConfirmationEmail
        $completedApp = Appointment::create([
            'guest_name' => 'Hoàng Bảo Châu',
            'guest_phone' => '0911222333',
            'guest_email' => 'chau.hoang@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Completed,
        ]);

        Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->call('updateStatus', $completedApp->id, AppointmentStatus::Confirmed->value)
            ->assertHasErrors('statusTransition')
            ->call('resendConfirmationEmail', $completedApp->id)
            ->assertHasErrors('statusTransition');
        $this->assertSame(AppointmentStatus::Completed, $completedApp->fresh()->status);
        $this->assertFalse($completedApp->sendOfficialConfirmationEmail($admin));

        // AccessoryOrder guard: Pending -> Completed blocked, stock untouched, Terminal -> Cancelled does NOT double-restore
        $pendingOrder = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Bùi Hải Đăng',
            'customer_phone' => '0911444555',
            'customer_email' => 'dang.bui@example.com',
            'customer_address' => '100 Nguyễn Thị Minh Khai, TP.HCM',
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('updateStatus', $pendingOrder->id, AccessoryOrder::STATUS_COMPLETED)
            ->assertHasErrors('statusTransition');
        $this->assertSame(AccessoryOrder::STATUS_PENDING, $pendingOrder->fresh()->status);
        $this->assertSame(10, $accessory->fresh()->stock);

        // Complete the order legitimately: Pending -> Confirmed -> Completed, then reject Completed -> Cancelled
        $pendingOrder->transitionToStatus(AccessoryOrder::STATUS_CONFIRMED, null, false, $admin);
        $pendingOrder->transitionToStatus(AccessoryOrder::STATUS_COMPLETED, null, false, $admin);

        $this->actingAs($admin)
            ->patch(route('admin.accessory-orders.update-status', $pendingOrder), [
                'status' => AccessoryOrder::STATUS_CANCELLED,
                'admin_notes' => 'Thử hủy sai quy trình',
            ])
            ->assertSessionHasErrors('status');
        $this->assertSame(AccessoryOrder::STATUS_COMPLETED, $pendingOrder->fresh()->status);
        $this->assertSame(10, $accessory->fresh()->stock);
        Mail::assertNothingQueued();

        // Saving admin_notes while keeping same terminal status still works idempotently
        $this->actingAs($admin)
            ->patch(route('admin.accessory-orders.update-status', $pendingOrder), [
                'status' => AccessoryOrder::STATUS_COMPLETED,
                'admin_notes' => 'Khách đã nhận đủ hóa đơn VAT.',
            ])
            ->assertRedirect(route('admin.accessory-orders.show', $pendingOrder));
        $this->assertSame('Khách đã nhận đủ hóa đơn VAT.', $pendingOrder->fresh()->admin_notes);

        // GuestAppointmentManageController blocks guest cancel on already Cancelled appointment
        $cancelledApp = Appointment::create([
            'guest_name' => 'Khách Đã Hủy',
            'guest_phone' => '0911666777',
            'guest_email' => 'cancelled.guest@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Cancelled,
        ]);
        $this->post($cancelledApp->temporaryManageUpdateUrl(), ['action' => 'cancel'])
            ->assertSessionHas('error');
    }

    public function test_three_crm_tables_preserve_8_sql_query_budget_on_cold_cache(): void
    {
        $admin = $this->createStaff();
        [$car, $accessory] = $this->createFixtures();

        Appointment::create([
            'guest_name' => 'Khách Lái Thử 01',
            'guest_phone' => '0900111222',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
        ]);
        Appointment::create([
            'guest_name' => 'Khách Bảo Dưỡng 01',
            'guest_phone' => '0900333444',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Confirmed,
        ]);
        AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện 01',
            'customer_phone' => '0900555666',
            'customer_address' => '1 Lê Duẩn, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_COMPLETED,
        ]);

        foreach ([
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
        ] as $url) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get($url)->assertOk();
            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queryCount, "Expected <= 8 SQL queries on {$url}, got {$queryCount}.");
        }
    }

    public function test_end_to_end_four_workflows_and_five_audit_edge_cases(): void
    {
        Mail::fake();

        $staffA = $this->createStaff();
        $staffB = $this->createStaff();
        [$car, $accessory] = $this->createFixtures();
        $accessory->update(['stock' => 2]);

        // =========================================================================
        // LUỒNG A: Lịch hẹn Lái thử / Tư vấn + Multi-tab Race + Input Validation + Switching
        // =========================================================================
        $this->post(route('appointments.store'), [
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'guest_name' => 'Phạm Quang Huy',
            'guest_phone' => '0909123789',
            'guest_email' => 'huy.pham@example.com',
            'appointment_date' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i'),
            'notes' => 'Đăng ký lái thử cao tốc.',
        ])->assertRedirect(route('appointments.success'));

        $testDrive = Appointment::query()->where('guest_email', 'huy.pham@example.com')->firstOrFail();
        $this->assertSame(AppointmentStatus::Pending, $testDrive->status);
        Mail::assertQueued(AppointmentConfirmationMail::class, fn (AppointmentConfirmationMail $mail): bool => $mail->appointment->id === $testDrive->id && $mail->mode === AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED);

        // Nhảy cóc pending -> completed bị chặn + Đầu vào bất thường (CONFIRMED / khoảng trắng / lạ) trả validation error sạch, không 500
        $drawer = Livewire::actingAs($staffA)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $testDrive->id)
            ->assertSee('action-confirm-appointment-'.$testDrive->id)
            ->assertSee('action-cancel-appointment-'.$testDrive->id)
            ->assertSee('Xem thư Hậu mãi trả góp')
            ->call('updateStatus', $testDrive->id, AppointmentStatus::Completed->value)
            ->assertHasErrors('statusTransition')
            ->call('updateStatus', $testDrive->id, 'CONFIRMED')
            ->assertHasErrors(['status', 'statusTransition'])
            ->call('updateStatus', $testDrive->id, ' confirmed ')
            ->assertHasErrors(['status', 'statusTransition'])
            ->call('updateStatus', $testDrive->id, 'unknown_status')
            ->assertHasErrors(['status', 'statusTransition']);
        $this->assertSame(AppointmentStatus::Pending, $testDrive->fresh()->status);

        // Staff A bấm Duyệt & Xác nhận (pending -> confirmed) -> gửi đúng 1 VIP Pass & ghi Timeline
        $drawer->call('updateStatus', $testDrive->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors()
            ->assertSee('action-complete-appointment-'.$testDrive->id)
            ->assertSee('action-cancel-appointment-'.$testDrive->id);

        $testDrive->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $testDrive->status);
        Mail::assertQueued(AppointmentConfirmationMail::class, 2); // 1 system_received + 1 admin_confirmed

        // Staff B (tab cũ vẫn mở trạng thái pending) bấm trùng Duyệt & Xác nhận -> nhận cảnh báo lỗi stale, KHÔNG gửi mail lần 2
        $timelineCountBeforeDuplicate = count($testDrive->meta_data['timeline'] ?? []);
        Livewire::actingAs($staffB)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $testDrive->id)
            ->call('updateStatus', $testDrive->id, AppointmentStatus::Confirmed->value)
            ->assertHasErrors('statusTransition')
            ->assertSee('đã ở trạng thái');
        $this->assertSame($timelineCountBeforeDuplicate, count($testDrive->fresh()->meta_data['timeline'] ?? []));
        Mail::assertQueued(AppointmentConfirmationMail::class, 2);

        // Chuyển nhanh qua lại giữa 2 hồ sơ khác nhau trong cùng phiên Drawer -> wire:key cập nhật đúng nút của hồ sơ thứ 2
        $secondPending = Appointment::create([
            'guest_name' => 'Trương Ngọc Ánh',
            'guest_phone' => '0909456123',
            'guest_email' => 'anh.truong@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Quote,
            'appointment_date' => now()->addDays(3)->setTime(15, 0),
            'status' => AppointmentStatus::Pending,
        ]);
        Livewire::actingAs($staffA)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $testDrive->id)
            ->assertSee('action-complete-appointment-'.$testDrive->id)
            ->call('inspectAppointment', $secondPending->id)
            ->assertSee('action-confirm-appointment-'.$secondPending->id)
            ->assertDontSee('action-complete-appointment-'.$testDrive->id);

        // Staff A bấm Hoàn tất qua Modal kết quả (confirmed -> completed) -> gửi thư cảm ơn đón tiếp, khóa cứng UI & chặn gửi lại mail
        Livewire::actingAs($staffA)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $testDrive->id)
            ->call('openCompletionModal', $testDrive->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_VISITED_NO_PURCHASE)
            ->call('confirmCompletion')
            ->assertHasNoErrors()
            ->assertSee('terminal-status-lock-appointment-'.$testDrive->id)
            ->assertSee('Đã đóng · Hoàn tất')
            ->call('resendConfirmationEmail', $testDrive->id)
            ->assertHasErrors('statusTransition');

        $testDrive->refresh();
        $this->assertSame(AppointmentStatus::Completed, $testDrive->status);
        $this->assertNotNull($testDrive->followup_email_sent_at);
        Mail::assertQueued(AppointmentCompletedThankYouMail::class, 1);

        // Cả Admin & Khách (Signed URL) đều bị chặn hủy/đổi lịch đã Completed
        $this->actingAs($staffA)
            ->patch(route('admin.appointments.update', $testDrive), ['status' => AppointmentStatus::Cancelled->value])
            ->assertSessionHasErrors('status');
        $this->post($testDrive->temporaryManageUpdateUrl(), ['action' => 'cancel'])->assertSessionHas('error');
        $this->post($testDrive->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => now()->addDays(5)->setTime(10, 0)->format('Y-m-d H:i'),
        ])->assertSessionHas('error');
        $this->assertSame(AppointmentStatus::Completed, $testDrive->fresh()->status);

        // =========================================================================
        // LUỒNG B: Đặt lịch Bảo dưỡng / Chăm sóc xe (4 mẫu preview, bỏ Hậu mãi trả góp)
        // =========================================================================
        $serviceBooking = Appointment::create([
            'guest_name' => 'Lý Hoàng Sơn',
            'guest_phone' => '0907888999',
            'guest_email' => 'son.ly@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(2)->setTime(9, 0),
            'status' => AppointmentStatus::Pending,
            'meta_data' => ['service_package' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)'],
        ]);
        $cancelledService = Appointment::create([
            'guest_name' => 'Trần Văn Bỏ',
            'guest_phone' => '0907000111',
            'guest_email' => 'bo.tran@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Detailing,
            'appointment_date' => now()->addDays(2)->setTime(11, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        Livewire::actingAs($staffA)
            ->test(ServiceBookingTable::class)
            ->call('inspectBooking', $serviceBooking->id)
            ->assertSee('Xem thư Hệ thống tiếp nhận')
            ->assertSee('Xác nhận xưởng dịch vụ')
            ->assertSee('Xem thư Khách đổi lịch')
            ->assertSee('Xem thư Nhắc lịch 24h')
            ->assertDontSee('Xem thư Hậu mãi trả góp')
            ->call('updateStatus', $serviceBooking->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors()
            ->call('openCompletionModal', $serviceBooking->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_SERVICE_DELIVERED)
            ->call('confirmCompletion')
            ->assertHasNoErrors()
            ->assertSee('terminal-status-lock-service-booking-'.$serviceBooking->id)
            ->call('inspectBooking', $cancelledService->id)
            ->call('updateStatus', $cancelledService->id, AppointmentStatus::Cancelled->value)
            ->assertHasNoErrors()
            ->assertSee('terminal-status-lock-service-booking-'.$cancelledService->id);

        // =========================================================================
        // LUỒNG C: Đơn hàng Phụ kiện & Tồn kho (stock=2 -> đặt 1 -> duyệt -> hủy -> hoàn +1, sửa ghi chú trên đơn đóng)
        // =========================================================================
        $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Vũ Minh Triết',
            'customer_phone' => '0918222333',
            'customer_email' => 'triet.vu@example.com',
            'customer_address' => '18 Lê Văn Lương, Hà Nội',
            'quantity' => 1,
        ])->assertRedirect();

        $order = AccessoryOrder::query()->where('customer_email', 'triet.vu@example.com')->firstOrFail();
        $this->assertSame(AccessoryOrder::STATUS_PENDING, $order->status);
        $this->assertSame(1, $accessory->fresh()->stock);
        Mail::assertQueued(AccessoryOrderConfirmationMail::class, 1);

        Livewire::actingAs($staffA)
            ->test(AccessoryOrderTable::class)
            ->call('inspectOrder', $order->id)
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CONFIRMED)
            ->assertHasNoErrors();
        $this->assertSame(AccessoryOrder::STATUS_CONFIRMED, $order->fresh()->status);
        $this->assertSame(1, $accessory->fresh()->stock);
        Mail::assertSent(AccessoryOrderStatusMail::class, 1);

        // Hủy đơn confirmed -> hoàn +1 về đúng stock = 2, Timeline ghi vết hoàn kho, Drawer khóa cứng
        Livewire::actingAs($staffA)
            ->test(AccessoryOrderTable::class)
            ->call('inspectOrder', $order->id)
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CANCELLED)
            ->assertHasNoErrors()
            ->assertSee('terminal-status-lock-accessory-order-'.$order->id)
            ->assertSee('Đã đóng · Đã hủy');
        $order->refresh();
        $this->assertSame(AccessoryOrder::STATUS_CANCELLED, $order->status);
        $this->assertSame(2, $accessory->fresh()->stock);
        $orderTimelineTitles = collect($order->meta_data['timeline'] ?? [])->pluck('title')->all();
        $this->assertContains('Hoàn 1 sản phẩm về kho', $orderTimelineTitles);

        // Multi-tab Staff B thử hủy hoặc duyệt lại đơn đã cancelled -> bị chặn, kho vẫn = 2
        Livewire::actingAs($staffB)
            ->test(AccessoryOrderTable::class)
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CANCELLED)
            ->assertHasErrors('statusTransition')
            ->call('updateStatus', $order->id, AccessoryOrder::STATUS_CONFIRMED)
            ->assertHasErrors('statusTransition');
        $this->assertSame(2, $accessory->fresh()->stock);

        // Vẫn lưu được admin_notes trên trang chi tiết đơn đã đóng
        $this->actingAs($staffA)
            ->patch(route('admin.accessory-orders.update-status', $order), [
                'status' => AccessoryOrder::STATUS_CANCELLED,
                'admin_notes' => 'Khách đổi mẫu khác, đã hoàn kho đầy đủ.',
            ])
            ->assertRedirect(route('admin.accessory-orders.show', $order));
        $this->assertSame('Khách đổi mẫu khác, đã hoàn kho đầy đủ.', $order->fresh()->admin_notes);

        // =========================================================================
        // LUỒNG D: Quyền tự phục vụ của Khách (Signed URL) & Cron Job appointments:cleanup-and-followup
        // =========================================================================
        $guestSelfServe = Appointment::create([
            'guest_name' => 'Đặng Thu Trang',
            'guest_phone' => '0936999888',
            'guest_email' => 'trang.dang@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(4)->setTime(14, 0),
            'status' => AppointmentStatus::Pending,
        ]);

        $newGuestDate = now()->addDays(6)->setTime(10, 0)->format('Y-m-d H:i');
        $this->post($guestSelfServe->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => $newGuestDate,
            'reason' => 'Khách bận công tác, tự dời lịch.',
        ])->assertRedirect();

        $guestSelfServe->refresh();
        $this->assertEmpty(
            collect($guestSelfServe->meta_data['timeline'] ?? [])->where('actor_type', 'admin')->all(),
            'Guest Signed URL flow must never record admin actor.'
        );

        Livewire::actingAs($staffA)
            ->test(AppointmentTable::class)
            ->call('acknowledgeGuestReschedule', $guestSelfServe->id)
            ->assertHasNoErrors();

        $guestSelfServe->refresh();
        $this->post($guestSelfServe->temporaryManageUpdateUrl(), ['action' => 'confirm'])->assertRedirect();
        $guestSelfServe->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $guestSelfServe->status);
        $customerActors = collect($guestSelfServe->meta_data['timeline'] ?? [])
            ->where('actor_type', 'customer')
            ->pluck('title')
            ->all();
        $this->assertContains('Khách hàng đã xác nhận sẽ đến lịch hẹn', $customerActors);
        $this->assertContains('Khách tự đổi ngày/giờ hẹn', $customerActors);

        // Cron job: tự hủy pending quá hạn (>24h), bỏ qua lịch confirmed/completed
        $overduePending = Appointment::create([
            'guest_name' => 'Quá Hạn Pending',
            'guest_phone' => '0919000111',
            'guest_email' => 'overdue.pending@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subHours(30),
            'status' => AppointmentStatus::Pending,
        ]);
        $overdueConfirmed = Appointment::create([
            'guest_name' => 'Quá Hạn Confirmed',
            'guest_phone' => '0919000222',
            'guest_email' => 'overdue.confirmed@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subHours(30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->artisan('appointments:cleanup-and-followup')->assertSuccessful();

        $this->assertSame(AppointmentStatus::Cancelled, $overduePending->fresh()->status);
        $this->assertSame(AppointmentStatus::Confirmed, $overdueConfirmed->fresh()->status);
        $this->assertSame(AppointmentStatus::Completed, $testDrive->fresh()->status);
    }
}
