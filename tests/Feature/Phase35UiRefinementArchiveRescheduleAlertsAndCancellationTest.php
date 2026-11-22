<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\NotificationBell;
use App\Livewire\Admin\ServiceBookingTable;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\GuestRescheduledAppointmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase35UiRefinementArchiveRescheduleAlertsAndCancellationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @return array{0: Category, 1: Product, 2: Product}
     */
    private function createCatalogFixtures(): array
    {
        $category = Category::factory()->create(['name' => 'BMW 5 Series', 'slug' => 'bmw-5-series-p35']);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport-p35',
            'type' => VehicleType::CAR,
            'price' => 3200000000,
            'deposit_amount' => 50000000,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Thảm Sàn All-Weather BMW',
            'slug' => 'tham-san-bmw-p35',
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
            'deposit_amount' => 0,
            'stock' => 20,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    public function test_date_time_picker_modes_dropdown_zindex_and_accounting_ui_cleanup(): void
    {
        [, $car] = $this->createCatalogFixtures();
        $topSuperAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);

        Carbon::setTestNow(Carbon::parse('2026-11-22 09:00:00', 'Asia/Ho_Chi_Minh'));

        // 1. /booking (mode=create): Digital HH:MM + Dual-Column Wheel inside Split Popover, single h-11 trigger, Public Dark 0px selects & z-index hierarchy
        $bookingHtml = $this->get(route('appointments.create'))->assertOk()->getContent();
        $this->assertStringContainsString('data-wheel-hours', $bookingHtml);
        $this->assertStringContainsString('data-wheel-minutes', $bookingHtml);
        $this->assertStringNotContainsString('sm:grid-cols-5', $bookingHtml);
        $this->assertStringContainsString('data-picker-mode="create"', $bookingHtml);
        $this->assertStringContainsString('data-picker-trigger="datetime"', $bookingHtml);
        $this->assertStringContainsString('data-picker-popover="split"', $bookingHtml);
        $this->assertStringContainsString('md:grid-cols-[1.35fr_1fr]', $bookingHtml);
        $this->assertStringContainsString('bg-[#0D1117]', $bookingHtml);
        $this->assertStringContainsString('border-white/15', $bookingHtml);
        $this->assertStringContainsString('h-11', $bookingHtml);
        $this->assertStringContainsString("panelOpen ? 'z-[70]' : 'z-30'", $bookingHtml);

        // 2. guest-manage (mode=reschedule): unified Split Popover, rounded-lg, no quick-date clutter, conditional preview line
        $appointment = Appointment::create([
            'guest_name' => 'Khách Đổi Lịch',
            'guest_phone' => '0901222333',
            'guest_email' => 'guest35@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => Carbon::parse('2026-11-28 09:30:00', 'Asia/Ho_Chi_Minh')->setTimezone('UTC'),
            'status' => AppointmentStatus::Pending,
        ]);

        $manageHtml = $this->get($appointment->temporaryManageUrl())->assertOk()->getContent();
        $this->assertStringContainsString('data-picker-mode="reschedule"', $manageHtml);
        $this->assertStringContainsString('data-picker-trigger="datetime"', $manageHtml);
        $this->assertStringContainsString('data-picker-popover="split"', $manageHtml);
        $this->assertStringNotContainsString('data-quick-date="today"', $manageHtml);
        $this->assertStringNotContainsString('data-quick-date="tomorrow"', $manageHtml);
        $this->assertStringContainsString('data-reschedule-preview', $manageHtml);
        $this->assertStringContainsString('Lịch hiện tại:', $manageHtml);
        $this->assertStringContainsString('Lịch đề xuất mới:', $manageHtml);

        // 3. /admin/accounting: Vietnamese breadcrumb, clean KPIs, disabled custom date inputs, Empty State when 0 vouchers
        $accountingHtml = $this->actingAs($topSuperAdmin)
            ->get(route('admin.accounting.index', [
                'period' => 'custom',
                'date_from' => '2026-10-01',
                'date_to' => '2026-10-02',
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Kế toán &amp; Sổ sách', $accountingHtml);
        $this->assertStringNotContainsString('1. Doanh thu phụ kiện thực thu', $accountingHtml);
        $this->assertStringContainsString('Doanh thu phụ kiện thực thu', $accountingHtml);
        $this->assertStringContainsString(':disabled="period !== \'custom\'"', $accountingHtml);
        $this->assertStringContainsString('Chưa có dữ liệu doanh thu trong kỳ', $accountingHtml);

        $presetAccountingHtml = $this->actingAs($topSuperAdmin)
            ->get(route('admin.accounting.index', ['period' => 'today']))
            ->assertOk()
            ->getContent();
        $this->assertStringContainsString(':disabled="period !== \'custom\'"', $presetAccountingHtml);
    }

    public function test_crm_archive_permissions_auto_archive_30_days_and_accounting_retention(): void
    {
        [, $car, $accessory] = $this->createCatalogFixtures();

        $topSuperAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);
        $regularAdmin = User::factory()->create(['email' => 'ops.p35@bmw.com', 'role' => User::ROLE_ADMIN, 'is_super_admin' => false]);
        $sales = User::factory()->sales()->create(['name' => 'Sales Lưu Trữ', 'email' => 'sales.p35@bmw.com']);

        $pendingAppt = Appointment::create([
            'guest_name' => 'Lịch Chờ Duyệt',
            'guest_phone' => '0911111111',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
            'assigned_to' => $sales->id,
        ]);

        $completedAppt = Appointment::create([
            'guest_name' => 'Lịch Đã Hoàn Tất',
            'guest_phone' => '0922222222',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
            'assigned_to' => $sales->id,
        ]);

        $staleServiceBooking = Appointment::create([
            'guest_name' => 'Dịch Vụ Cũ 35 Ngày',
            'guest_phone' => '0933333333',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->subDays(35),
            'status' => AppointmentStatus::Completed,
            'assigned_to' => $sales->id,
        ]);
        $oldUpdatedAt = now()->subDays(35)->startOfMinute();
        DB::table('appointments')->where('id', $staleServiceBooking->id)->update(['updated_at' => $oldUpdatedAt]);

        $staleOrder = AccessoryOrder::factory()->completed()->assignedTo($sales)->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Đơn Phụ Kiện Cũ 32 Ngày',
            'quantity' => 2,
        ]);
        DB::table('accessory_orders')->where('id', $staleOrder->id)->update(['updated_at' => now()->subDays(32)]);

        // Regular admin and sales get 403 when attempting archive/unarchive actions
        Livewire::actingAs($regularAdmin)
            ->test(AppointmentTable::class)
            ->assertDontSee('data-action="archive-all-closed"', false)
            ->call('archiveRecord', $completedAppt->id)
            ->assertForbidden();

        Livewire::actingAs($sales)
            ->test(AccessoryOrderTable::class)
            ->call('archiveAllClosed')
            ->assertForbidden();

        // Top Super Admin cannot archive a pending appointment
        Livewire::actingAs($topSuperAdmin)
            ->test(AppointmentTable::class)
            ->call('archiveRecord', $pendingAppt->id)
            ->assertSet('errorMessage', "Không thể chuyển trạng thái từ 'Chờ xác nhận' sang 'Kho lưu trữ'. Chỉ cho phép lưu trữ hồ sơ đã hoàn tất hoặc đã hủy.");

        // Top Super Admin archives completed appointment without altering original updated_at
        $completedUpdatedBefore = $completedAppt->fresh()->updated_at->format('Y-m-d H:i:s');
        Livewire::actingAs($topSuperAdmin)
            ->test(AppointmentTable::class)
            ->call('archiveRecord', $completedAppt->id)
            ->assertDontSee('Lịch Đã Hoàn Tất')
            ->assertSee('Lịch Chờ Duyệt')
            ->call('setArchiveScope', 'archived')
            ->assertSee('Lịch Đã Hoàn Tất')
            ->call('inspectAppointment', $completedAppt->id)
            ->assertSee($topSuperAdmin->name);

        $completedAppt->refresh();
        $this->assertTrue($completedAppt->isArchived());
        $this->assertSame((int) $topSuperAdmin->id, (int) $completedAppt->archived_by);
        $this->assertSame($completedUpdatedBefore, $completedAppt->updated_at->format('Y-m-d H:i:s'));

        // Unarchive restores cleanly
        Livewire::actingAs($topSuperAdmin)
            ->test(AppointmentTable::class)
            ->call('setArchiveScope', 'archived')
            ->call('unarchiveRecord', $completedAppt->id)
            ->call('setArchiveScope', 'active')
            ->assertSee('Lịch Đã Hoàn Tất');
        $this->assertFalse($completedAppt->fresh()->isArchived());

        // Auto-archive stale >30 day closed records via Artisan command (dry-run first, then real)
        $this->assertSame(0, Artisan::call('showroom:archive-stale-records', ['--dry-run' => true]));
        $this->assertFalse($staleServiceBooking->fresh()->isArchived());
        $this->assertFalse($staleOrder->fresh()->isArchived());

        $this->assertSame(0, Artisan::call('showroom:archive-stale-records'));
        $this->assertTrue($staleServiceBooking->fresh()->isArchived());
        $this->assertTrue($staleOrder->fresh()->isArchived());
        $this->assertFalse($completedAppt->fresh()->isArchived());
        $this->assertFalse($pendingAppt->fresh()->isArchived());
        $this->assertSame(
            $oldUpdatedAt->format('Y-m-d H:i:s'),
            $staleServiceBooking->fresh()->updated_at->format('Y-m-d H:i:s')
        );

        // Accounting keeps ALL archived orders/appointments and renders 'Đã lưu trữ' on day ledger
        $this->actingAs($topSuperAdmin)
            ->get(route('admin.accounting.index'))
            ->assertOk()
            ->assertSee('9.000.000');

        $todayVn = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $this->actingAs($topSuperAdmin)
            ->get(route('admin.accounting.day', ['date' => $todayVn]))
            ->assertOk()
            ->assertSee('Đơn Phụ Kiện Cũ 32 Ngày')
            ->assertSee('Đã lưu trữ');

        // Bulk archive all closed via Livewire
        Livewire::actingAs($topSuperAdmin)
            ->test(AppointmentTable::class)
            ->call('archiveAllClosed');
        $this->assertTrue($completedAppt->fresh()->isArchived());
        $this->assertFalse($pendingAppt->fresh()->isArchived());

        // Cold-cache <= 8 SQL query budget preserved on all 3 CRM routes
        foreach ([
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
        ] as $routeUrl) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($topSuperAdmin)->get($routeUrl)->assertOk();
            $queriesCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queriesCount, "Cold-cache SQL query budget exceeded on {$routeUrl}: {$queriesCount}");
        }
    }

    public function test_guest_reschedule_triggers_notifications_drawer_alert_card_and_acknowledgement(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        $admin = User::factory()->create(['email' => 'admin.resched35@bmw.com', 'role' => User::ROLE_ADMIN]);
        $assignedSales = User::factory()->sales()->create(['name' => 'Sales Phụ Trách', 'email' => 'sales.assigned@bmw.com']);
        $otherSales = User::factory()->sales()->create(['name' => 'Sales Khác', 'email' => 'sales.other@bmw.com']);
        $marketing = User::factory()->marketing()->create(['email' => 'mkt.resched35@bmw.com']);

        $appointment = Appointment::create([
            'guest_name' => 'Nguyễn Hoàng Đổi Lịch',
            'guest_phone' => '0909888777',
            'guest_email' => 'hoang.resched@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(9, 30),
            'status' => AppointmentStatus::Confirmed,
            'assigned_to' => $assignedSales->id,
        ]);

        $newSlotDate = now('Asia/Ho_Chi_Minh')->addDays(4)->format('Y-m-d');
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $newSlotDate,
            'new_time' => '14:30',
            'new_appointment_date' => "{$newSlotDate} 14:30",
            'reason' => 'Kẹt lịch họp buổi sáng cần dời sang chiều.',
        ])->assertRedirect();

        // Notification sent to Admin & Assigned Sales ONLY (not other Sales or Marketing)
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $admin->id,
            'type' => GuestRescheduledAppointmentNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $assignedSales->id,
            'type' => GuestRescheduledAppointmentNotification::class,
        ]);
        $this->assertSame(
            0,
            DatabaseNotification::query()
                ->where('notifiable_type', (new User)->getMorphClass())
                ->whereIn('notifiable_id', [$otherSales->id, $marketing->id])
                ->where('type', GuestRescheduledAppointmentNotification::class)
                ->count()
        );

        // NotificationBell shows KHÁCH ĐỔI LỊCH pill
        Livewire::actingAs($assignedSales)
            ->test(NotificationBell::class)
            ->assertSee('KHÁCH ĐỔI LỊCH')
            ->assertSee($appointment->publicReferenceCode());

        // AppointmentTable displays row badge and Drawer amber alert card, and acknowledge clears flag
        Livewire::actingAs($assignedSales)
            ->test(AppointmentTable::class)
            ->assertSee('Khách vừa đổi lịch')
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('data-reschedule-alert-card="'.$appointment->id.'"', false)
            ->assertSee('Kẹt lịch họp buổi sáng cần dời sang chiều.')
            ->assertSee('Xác nhận đã tiếp nhận lịch mới')
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->assertDontSee('data-reschedule-alert-card="'.$appointment->id.'"', false);

        $freshMeta = $appointment->fresh()->meta_data;
        $this->assertFalse((bool) ($freshMeta['reschedule_pending_review'] ?? true));
    }

    public function test_cancellation_attribution_distinguishes_customer_staff_and_system_across_all_views(): void
    {
        Mail::fake();
        [, $car, $accessory] = $this->createCatalogFixtures();

        $topSuperAdmin = User::factory()->topSuperAdmin()->create(['name' => 'Thái Super Admin', 'email' => 'thai@bmw.com']);
        $sales = User::factory()->sales()->create(['name' => 'Lê Anh Đức', 'email' => 'sales.duc35@bmw.com']);

        // 1. Customer self-cancellation via guest-manage
        $customerCancelled = Appointment::create([
            'guest_name' => 'Khách Tự Hủy',
            'guest_phone' => '0901000111',
            'guest_email' => 'cust.cancel@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->post($customerCancelled->temporaryManageUpdateUrl(), [
            'action' => 'cancel',
            'reason' => 'Đi công tác đột xuất.',
        ])->assertRedirect();

        $this->assertSame('Khách hàng tự hủy', $customerCancelled->fresh()->cancellationAttributionLabel());
        $this->get($customerCancelled->temporaryManageUrl())
            ->assertOk()
            ->assertSee('Khách hàng tự hủy')
            ->assertSee('Đi công tác đột xuất.');

        // 2. Sales cancels an appointment
        $salesCancelled = Appointment::create([
            'guest_name' => 'Hồ Sơ Sales Hủy',
            'guest_phone' => '0902000222',
            'product_id' => $car->id,
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
            'assigned_to' => $sales->id,
        ]);

        Livewire::actingAs($sales)
            ->test(ServiceBookingTable::class)
            ->call('updateStatus', $salesCancelled->id, AppointmentStatus::Cancelled->value);
        $this->assertSame('Sales hủy: Lê Anh Đức', $salesCancelled->fresh()->cancellationAttributionLabel());

        // 3. System auto-cancels overdue >24h pending appointment
        $systemCancelled = Appointment::create([
            'guest_name' => 'Quá Hạn Hệ Thống Hủy',
            'guest_phone' => '0903000333',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->subHours(30),
            'status' => AppointmentStatus::Pending,
        ]);
        Artisan::call('appointments:cleanup-and-followup');
        $this->assertSame('Hệ thống tự hủy quá hạn', $systemCancelled->fresh()->cancellationAttributionLabel());

        // 4. Admin cancels an AccessoryOrder
        $orderCancelledByAdmin = AccessoryOrder::factory()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Đơn Phụ Kiện Admin Hủy',
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Livewire::actingAs($topSuperAdmin)
            ->test(AccessoryOrderTable::class)
            ->call('updateStatus', $orderCancelledByAdmin->id, AccessoryOrder::STATUS_CANCELLED)
            ->assertSee('Quản trị viên hủy: Thái Super Admin')
            ->call('inspectOrder', $orderCancelledByAdmin->id)
            ->assertSee('Quản trị viên hủy: Thái Super Admin');

        // 5. CRM AppointmentTable, ServiceBookingTable, AccessoryOrder show & Accounting day ledger show attributions
        Livewire::actingAs($topSuperAdmin)
            ->test(AppointmentTable::class)
            ->assertSee('Khách hàng tự hủy')
            ->assertSee('Hệ thống tự hủy quá hạn')
            ->call('inspectAppointment', $customerCancelled->id)
            ->assertSee('Khách hàng tự hủy');

        Livewire::actingAs($topSuperAdmin)
            ->test(ServiceBookingTable::class)
            ->assertSee('Sales hủy: Lê Anh Đức');

        $this->actingAs($topSuperAdmin)
            ->get(route('admin.accessory-orders.show', $orderCancelledByAdmin))
            ->assertOk()
            ->assertSee('Quản trị viên hủy: Thái Super Admin');

        $todayVn = now('Asia/Ho_Chi_Minh')->format('Y-m-d');
        $this->actingAs($topSuperAdmin)
            ->get(route('admin.accounting.day', ['date' => $todayVn]))
            ->assertOk()
            ->assertSee('Khách hàng tự hủy')
            ->assertSee('Sales hủy: Lê Anh Đức')
            ->assertSee('Hệ thống tự hủy quá hạn')
            ->assertSee('Quản trị viên hủy: Thái Super Admin');
    }
}
