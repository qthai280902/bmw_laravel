<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Livewire\BookingFormWizard;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\GuestRescheduledAppointmentNotification;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class Phase36SmartPickerVersionedRescheduleCancelledLockAndPostVisitMailsTest extends TestCase
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
        $category = Category::factory()->create([
            'name' => 'BMW M Series',
            'slug' => 'bmw-m-series-p36',
        ]);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW M3 Competition Sedan',
            'slug' => 'bmw-m3-competition-p36',
            'type' => VehicleType::CAR,
            'price' => 4999000000,
            'deposit_amount' => 50000000,
            'stock' => 4,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Bộ mâm M Performance 20-inch',
            'slug' => 'bo-mam-m-performance-p36',
            'type' => VehicleType::ACCESSORY,
            'price' => 135000000,
            'deposit_amount' => 0,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    public function test_smart_split_popover_date_time_picker_and_guest_manage_reschedule_ux(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        Carbon::setTestNow(Carbon::parse('2026-11-22 09:00:00', 'Asia/Ho_Chi_Minh'));

        // 1. /booking renders unified Split Popover in dark 0px theme without quick-date button clutter
        $bookingHtml = $this->get(route('appointments.create'))->assertOk()->getContent();
        $this->assertStringContainsString('data-picker-trigger="datetime"', $bookingHtml);
        $this->assertStringContainsString('data-picker-popover="split"', $bookingHtml);
        $this->assertStringContainsString('md:grid-cols-[1.35fr_1fr]', $bookingHtml);
        $this->assertStringContainsString('rounded-none', $bookingHtml);
        $this->assertStringContainsString('bg-[#0D1117]', $bookingHtml);
        $this->assertStringContainsString('border-white/15', $bookingHtml);
        $this->assertStringNotContainsString('data-quick-date=', $bookingHtml);
        $this->assertStringNotContainsString('Bước 1 · Chọn ngày hẹn', $bookingHtml);

        // 2. guest-manage renders Split Popover in light rounded-lg theme, zero rounded-full, conditional preview, and blocks unchanged reschedule
        $appointmentDate = Carbon::parse('2026-11-27 15:30:00', 'Asia/Ho_Chi_Minh')->setTimezone('UTC');
        $appointment = Appointment::create([
            'guest_name' => 'Nguyễn Minh Hoàng',
            'guest_phone' => '0901363636',
            'guest_email' => 'hoang.nguyen36@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => $appointmentDate,
            'status' => AppointmentStatus::Confirmed,
        ]);

        $manageHtml = $this->get($appointment->temporaryManageUrl())->assertOk()->getContent();
        $this->assertStringContainsString('data-picker-trigger="datetime"', $manageHtml);
        $this->assertStringContainsString('data-picker-popover="split"', $manageHtml);
        $this->assertStringContainsString('x-show="hasChanged()"', $manageHtml);
        $this->assertStringContainsString(':disabled="!hasChanged() || isFrozen"', $manageHtml);
        $guestLuxurySection = (string) strstr($manageHtml, '<section class="guest-luxury');
        $guestLuxurySection = (string) strstr($guestLuxurySection, '</section>', true);
        $this->assertNotEmpty($guestLuxurySection);
        $this->assertStringNotContainsString('rounded-full', $guestLuxurySection);
        $this->assertStringNotContainsString('+1 ngày', $manageHtml);
        $this->assertStringNotContainsString('+1 tuần', $manageHtml);

        // Attempting to reschedule to the exact same date & time fails validation cleanly
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => $appointment->appointment_date->format('Y-m-d H:i'),
        ])->assertSessionHasErrors('new_appointment_date');
    }

    public function test_customer_emails_always_attribute_assigned_sales_and_deduplicate_role_labels(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        $topAdmin = User::factory()->topSuperAdmin()->create([
            'name' => 'Nguyễn Quốc Thái - Quản Lý',
            'email' => 'thai@bmw.com',
        ]);
        $assignedSales = User::factory()->sales()->create([
            'name' => 'Trần Minh Đức',
            'email' => 'sales.duc36@bmw.com',
        ]);

        // Case 1: Assigned appointment confirmed by Top Super Admin -> Email MUST show assigned Sales, never overwritten by Top Admin
        $assignedAppointment = Appointment::create([
            'guest_name' => 'Lê Bảo Ngọc',
            'guest_phone' => '0902363636',
            'guest_email' => 'ngoc.le36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $assignedSales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Pending,
        ]);

        $assignedAppointment->transitionStatusWithAutomation(AppointmentStatus::Confirmed, $topAdmin);

        $previewResponse = $this->actingAs($topAdmin)
            ->get(route('admin.mail-preview.appointment', [
                'appointment' => $assignedAppointment,
                'mode' => AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
            ]))
            ->assertOk();

        $renderedHtml = $previewResponse->getContent();
        $this->assertStringContainsString('Trần Minh Đức (Tư Vấn Bán Hàng)', $renderedHtml);
        $this->assertStringNotContainsString('Nguyễn Quốc Thái - Quản Lý', $renderedHtml);

        // Case 2: Unassigned appointment confirmed by 'Nguyễn Quốc Thái - Quản Lý' -> role 'Quản Lý' is NOT duplicated
        $unassignedAppointment = Appointment::create([
            'guest_name' => 'Phạm Gia Huy',
            'guest_phone' => '0903363636',
            'guest_email' => 'huy.pham36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => null,
            'type' => AppointmentType::Quote,
            'appointment_date' => now()->addDays(3)->setTime(14, 30),
            'status' => AppointmentStatus::Pending,
        ]);
        $unassignedAppointment->forceFill(['assigned_to' => null])->saveQuietly();

        $advisorDisplay = AppointmentConfirmationMail::resolveAdvisorDisplay(
            $unassignedAppointment->fresh(),
            $topAdmin->name,
            $topAdmin->roleLabel(),
        );

        $this->assertSame('Nguyễn Quốc Thái - Quản Lý', $advisorDisplay['formatted']);
        $this->assertStringNotContainsString('(Quản Lý)', $advisorDisplay['formatted']);
    }

    public function test_versioned_signed_url_locks_pending_reschedule_freezes_old_links_and_resends_new_link_on_acknowledge(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        $sales = User::factory()->sales()->create([
            'name' => 'Hoàng Gia Bảo',
            'email' => 'sales.bao36@bmw.com',
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'Đặng Thu Thảo',
            'guest_phone' => '0904363636',
            'guest_email' => 'thao.dang36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(9, 30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $v1ManageUrl = $appointment->temporaryManageUrl();
        $v1UpdateUrl = $appointment->temporaryManageUpdateUrl();
        $this->assertStringContainsString('v=1', $v1ManageUrl);

        // Missing ?v when manage_version === 1 is treated as v=1 for backward compatibility
        $legacyUrlWithoutV = URL::temporarySignedRoute('appointments.guest.manage', now()->addDays(14), ['appointment' => $appointment->id]);
        $this->get($legacyUrlWithoutV)->assertOk()->assertDontSee('data-outdated-link-banner', false);

        // Customer reschedules via v=1 update URL -> increments manage_version to 2, locks pending review, redirects to v=2 URL
        $newTargetSlot = now()->addDays(4)->setTime(15, 30)->format('Y-m-d H:i');
        $rescheduleResponse = $this->post($v1UpdateUrl, [
            'action' => 'reschedule',
            'new_appointment_date' => $newTargetSlot,
            'reason' => 'Xin đổi sang chiều thứ Bảy.',
        ]);

        $appointment->refresh();
        $this->assertSame(2, $appointment->currentManageVersion());
        $this->assertTrue($appointment->hasPendingRescheduleReview());
        $v2ManageUrl = $appointment->temporaryManageUrl();
        $this->assertStringContainsString('v=2', $v2ManageUrl);
        $rescheduleResponse->assertRedirect($v2ManageUrl);

        Mail::assertQueued(GuestAppointmentChangeMail::class, function (GuestAppointmentChangeMail $mail) use ($appointment): bool {
            return $mail->appointment->id === $appointment->id
                && $mail->appointment->currentManageVersion() === 2;
        });

        // Visiting v=2 while reschedule_pending_review === true renders 1 unified banner and freezes all 3 action cards
        $v2Html = $this->get($v2ManageUrl)->assertOk()->getContent();
        $this->assertStringContainsString('data-reschedule-pending-banner', $v2Html);
        $this->assertSame(1, substr_count($v2Html, 'Chuyên viên BMW Showroom sẽ kiểm tra'));
        $this->assertStringContainsString('opacity-60 pointer-events-none select-none', $v2Html);

        // Backend also blocks confirm / reschedule / cancel while reschedule_pending_review === true
        $v2UpdateUrl = $appointment->temporaryManageUpdateUrl();
        $this->post($v2UpdateUrl, ['action' => 'confirm'])->assertSessionHas('error');
        $this->post($v2UpdateUrl, ['action' => 'cancel'])->assertSessionHas('error');

        // Visiting outdated v=1 URL shows outdated link banner and rejects POST actions with HTTP 403 (web) / 422 (JSON) without leaking v=2 URL
        $outdatedHtml = $this->get($v1ManageUrl)->assertOk()->assertSee('data-outdated-link-banner', false)->getContent();
        $this->assertStringNotContainsString('v=2', $outdatedHtml);
        $this->post($v1UpdateUrl, ['action' => 'confirm'])->assertForbidden();
        $this->postJson($v1UpdateUrl, ['action' => 'confirm'])->assertStatus(422);

        // Staff clicks "Xác nhận đã tiếp nhận lịch mới" -> clears pending review, marks notification read, and sends fresh confirmation email with v=2 link
        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('acknowledgeGuestReschedule', $appointment->id)
            ->assertHasNoErrors();

        $appointment->refresh();
        $this->assertFalse($appointment->hasPendingRescheduleReview());

        Mail::assertQueued(AppointmentConfirmationMail::class, function (AppointmentConfirmationMail $mail) use ($appointment): bool {
            return $mail->appointment->id === $appointment->id
                && $mail->mode === AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED
                && str_contains($mail->appointment->temporaryManageUrl(), 'v=2');
        });
    }

    public function test_cancelled_records_clear_reschedule_alerts_lock_sales_assignment_and_crm_search_placeholders_are_updated(): void
    {
        Mail::fake();
        [, $car, $accessory] = $this->createCatalogFixtures();

        $topAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);
        $salesA = User::factory()->sales()->create(['name' => 'Sales A', 'email' => 'sales.a36@bmw.com']);
        $salesB = User::factory()->sales()->create(['name' => 'Sales B', 'email' => 'sales.b36@bmw.com']);

        $appointment = Appointment::create([
            'guest_name' => 'Vũ Quang Hải',
            'guest_phone' => '0905363636',
            'guest_email' => 'hai.vu36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $salesA->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Confirmed,
            'meta_data' => [
                'reschedule_pending_review' => true,
                'previous_appointment_date' => '08:30 — 24/11/2026',
                'reschedule_reason' => 'Khách xin dời 2 tiếng.',
            ],
        ]);

        app(AdminNotificationDispatcher::class)->notifyGuestRescheduled(
            appointment: $appointment,
            previousDateLabel: '08:30 — 24/11/2026',
            newDateLabel: '10:30 — 24/11/2026',
            reason: 'Khách xin dời 2 tiếng.',
        );

        $this->assertGreaterThan(
            0,
            DatabaseNotification::query()
                ->where('type', GuestRescheduledAppointmentNotification::class)
                ->whereNull('read_at')
                ->count()
        );

        // Staff cancels appointment -> clears reschedule_pending_review and marks reschedule notification as read
        Livewire::actingAs($topAdmin)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->call('updateStatus', $appointment->id, AppointmentStatus::Cancelled->value)
            ->assertHasNoErrors()
            ->assertDontSee('Khách vừa đổi lịch')
            ->assertDontSee('data-reschedule-alert-card="'.$appointment->id.'"', false)
            ->assertSee('data-terminal-row="true"', false)
            ->assertSee('opacity-55', false)
            ->call('assignToSales', $appointment->id, $salesB->id)
            ->assertSet('errorMessage', 'Hồ sơ đã bị hủy và bị khóa thao tác nghiệp vụ, không thể thay đổi phân công chuyên viên Sales.');

        $appointment->refresh();
        $this->assertFalse((bool) ($appointment->meta_data['reschedule_pending_review'] ?? true));
        $this->assertSame($salesA->id, (int) $appointment->assigned_to);
        $this->assertSame(
            0,
            DatabaseNotification::query()
                ->where('type', GuestRescheduledAppointmentNotification::class)
                ->whereNull('read_at')
                ->count()
        );

        // Cancelled AccessoryOrder also blocks assignToSales & locks admin/accessory-orders/show
        $cancelledOrder = AccessoryOrder::create([
            'customer_name' => 'Trịnh Công Sơn',
            'customer_phone' => '0906363636',
            'customer_email' => 'son.trinh36@customer.vn',
            'customer_address' => '123 Lê Lợi, Q.1, TP.HCM',
            'product_id' => $accessory->id,
            'assigned_to' => $salesA->id,
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_CANCELLED,
        ]);

        Livewire::actingAs($topAdmin)
            ->test(AccessoryOrderTable::class)
            ->call('assignToSales', $cancelledOrder->id, $salesB->id)
            ->assertSet('errorMessage', 'Hồ sơ đã bị hủy và bị khóa thao tác nghiệp vụ, không thể thay đổi phân công chuyên viên Sales.');

        $this->assertSame($salesA->id, (int) $cancelledOrder->fresh()->assigned_to);

        $this->actingAs($topAdmin)
            ->get(route('admin.accessory-orders.show', $cancelledOrder))
            ->assertOk()
            ->assertSee('Đã hủy · Khóa thao tác nghiệp vụ')
            ->assertSee('Đã khóa cập nhật (Đơn đã hủy)');

        // All 3 CRM tables show updated search placeholders without #SĐT_STT
        $this->actingAs($topAdmin)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Mã BMW/CT/KH, tên, SĐT, xe...')
            ->assertDontSee('#SĐT_STT');

        $this->actingAs($topAdmin)
            ->get(route('admin.service-bookings.index'))
            ->assertOk()
            ->assertSee('Mã SRV/CT/KH, tên, SĐT, biển số...')
            ->assertDontSee('#SĐT_STT');

        $this->actingAs($topAdmin)
            ->get(route('admin.accessory-orders.index'))
            ->assertOk()
            ->assertSee('Mã ACC/CT/KH, tên, SĐT, phụ kiện...')
            ->assertDontSee('#SĐT_STT');
    }

    public function test_custom_dark_cockpit_confirm_archive_modal_replaces_browser_wire_confirm(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        $topAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);

        $completedAppointment = Appointment::create([
            'guest_name' => 'Lý Hải Đăng',
            'guest_phone' => '0907363636',
            'guest_email' => 'dang.ly36@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->subDay(),
            'status' => AppointmentStatus::Completed,
        ]);

        $component = Livewire::actingAs($topAdmin)
            ->test(AppointmentTable::class);

        $this->assertStringNotContainsString('wire:confirm', $component->html());

        $component->call('inspectAppointment', $completedAppointment->id)
            ->call('promptArchiveRecord', $completedAppointment->id, $completedAppointment->publicReferenceCode())
            ->assertSet('pendingArchiveAction', 'archive')
            ->assertSee('data-confirm-archive-modal="archive"', false)
            ->assertSee('Dữ liệu Kế toán &amp; Sổ sách vẫn được bảo toàn 100%.', false)
            ->call('confirmPendingArchiveAction')
            ->assertSet('pendingArchiveAction', null);

        $this->assertTrue($completedAppointment->fresh()->isArchived());
    }

    public function test_two_branch_vehicle_appointment_completion_purchased_vs_visited_and_cold_cache_query_budget(): void
    {
        Mail::fake();
        [, $car, $accessory] = $this->createCatalogFixtures();

        Carbon::setTestNow(Carbon::parse('2026-11-22 10:00:00', 'Asia/Ho_Chi_Minh'));

        $topAdmin = User::factory()->topSuperAdmin()->create(['email' => 'thai@bmw.com']);
        $sales = User::factory()->sales()->create([
            'name' => 'Đỗ Hoàng Nam',
            'email' => 'sales.nam36@bmw.com',
        ]);

        // Branch 1: Purchased outcome -> generates 1-month BMW-CARE voucher, sends purchased thank-you email (never blocked by GmailQuotaGuard), skips PostTestDriveFollowupMail
        $purchasedAppointment = Appointment::create([
            'guest_name' => 'Trần Đại Nghĩa',
            'guest_phone' => '0908363636',
            'guest_email' => 'nghia.tran36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addHours(3),
            'status' => AppointmentStatus::Confirmed,
        ]);

        // Simulate Gmail soft limit reached -> transactional AppointmentCompletedThankYouMail still sends!
        $quotaGuard = app(GmailQuotaGuard::class);
        Cache::put('mail:gmail_quota:sent:'.now()->format('Y-m-d'), $quotaGuard->dailySoftLimit() + 5, now()->addDay());

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('openCompletionModal', $purchasedAppointment->id)
            ->assertSet('completionTargetId', $purchasedAppointment->id)
            ->assertSee('data-completion-outcome-modal="'.$purchasedAppointment->id.'"', false)
            ->set('completionOutcome', Appointment::OUTCOME_PURCHASED)
            ->call('confirmCompletion')
            ->assertHasNoErrors();

        $purchasedAppointment->refresh();
        $this->assertSame(AppointmentStatus::Completed, $purchasedAppointment->status);
        $this->assertSame(Appointment::OUTCOME_PURCHASED, $purchasedAppointment->completionOutcome());
        $this->assertSame('Đã mua xe', $purchasedAppointment->completionOutcomeLabel());
        $this->assertMatchesRegularExpression('/^BMW-CARE-[A-Z0-9]{6}$/', (string) $purchasedAppointment->careVoucherCode());
        $this->assertSame('22/12/2026', $purchasedAppointment->careVoucherExpiresLabel());

        Mail::assertQueued(AppointmentCompletedThankYouMail::class, function (AppointmentCompletedThankYouMail $mail) use ($purchasedAppointment): bool {
            $html = $mail->render();

            return $mail->appointment->id === $purchasedAppointment->id
                && $mail->isPurchased()
                && str_contains($html, (string) $purchasedAppointment->careVoucherCode())
                && str_contains($html, '22/12/2026')
                && str_contains($html, 'Đỗ Hoàng Nam (Tư Vấn Bán Hàng)');
        });
        Mail::assertNotQueued(PostTestDriveFollowupMail::class);

        // Branch 2: Visited no purchase outcome -> NO voucher generated, email thanks visit with NO voucher/car-wash mention
        $visitedAppointment = Appointment::create([
            'guest_name' => 'Ngô Thanh Vân',
            'guest_phone' => '0909363636',
            'guest_email' => 'van.ngo36@customer.vn',
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addHours(4),
            'status' => AppointmentStatus::Confirmed,
        ]);

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('handleInlineStatusChange', $visitedAppointment->id, AppointmentStatus::Completed->value)
            ->assertSet('completionTargetId', $visitedAppointment->id)
            ->set('completionOutcome', Appointment::OUTCOME_VISITED_NO_PURCHASE)
            ->call('confirmCompletion')
            ->assertHasNoErrors();

        $visitedAppointment->refresh();
        $this->assertSame(AppointmentStatus::Completed, $visitedAppointment->status);
        $this->assertSame(Appointment::OUTCOME_VISITED_NO_PURCHASE, $visitedAppointment->completionOutcome());
        $this->assertSame('Chưa mua xe', $visitedAppointment->completionOutcomeLabel());
        $this->assertNull($visitedAppointment->careVoucherCode());

        Mail::assertQueued(AppointmentCompletedThankYouMail::class, function (AppointmentCompletedThankYouMail $mail) use ($visitedAppointment): bool {
            if ($mail->appointment->id !== $visitedAppointment->id) {
                return false;
            }
            $html = $mail->render();

            return ! $mail->isPurchased()
                && str_contains($html, 'Đỗ Hoàng Nam (Tư Vấn Bán Hàng)')
                && ! str_contains($html, 'BMW-CARE-')
                && ! str_contains($html, 'Rửa xe tiêu chuẩn Detailing');
        });

        // Cleanup command does not send PostTestDriveFollowupMail for appointments with completion_outcome
        $this->artisan('appointments:cleanup-and-followup')->assertSuccessful();
        Mail::assertNotQueued(PostTestDriveFollowupMail::class);

        // Preview endpoints & Accounting daily ledger show the new badges and templates
        $this->actingAs($topAdmin)
            ->get(route('admin.mail-preview.appointment', ['appointment' => $purchasedAppointment, 'mode' => 'completed_purchased']))
            ->assertOk()
            ->assertSee((string) $purchasedAppointment->careVoucherCode());

        $this->actingAs($topAdmin)
            ->get(route('admin.mail-preview.appointment', ['appointment' => $visitedAppointment, 'mode' => 'completed_visited']))
            ->assertOk()
            ->assertSee('Trân trọng cảm ơn Quý khách đã dành thời gian ghé thăm BMW Showroom')
            ->assertDontSee('BMW-CARE-');

        $this->actingAs($topAdmin)
            ->get(route('admin.accounting.day', ['date' => '2026-11-22']))
            ->assertOk()
            ->assertSee('Đã mua xe · '.$purchasedAppointment->careVoucherCode())
            ->assertSee('Chưa mua xe');

        // Verify cold-cache SQL query budget <= 8 on all 3 CRM screens
        Appointment::create([
            'guest_name' => 'Khách Dịch Vụ',
            'guest_phone' => '0911363636',
            'guest_email' => 'srv36@customer.vn',
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Pending,
        ]);

        AccessoryOrder::create([
            'customer_name' => 'Khách Phụ Kiện',
            'customer_phone' => '0912363636',
            'customer_email' => 'acc36@customer.vn',
            'customer_address' => '88 Nguyễn Huệ, Q.1, TP.HCM',
            'product_id' => $accessory->id,
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        foreach ([AppointmentTable::class, ServiceBookingTable::class, AccessoryOrderTable::class] as $componentClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::actingAs($topAdmin)->test($componentClass)->assertOk();

            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queryCount, "{$componentClass} exceeded <= 8 cold-cache SQL query budget (ran {$queryCount} queries).");
        }
    }

    public function test_phase36_1_digital_time_input_dual_wheel_flexible_showroom_hours_and_booking_summary_card(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        Carbon::setTestNow(Carbon::parse('2026-11-23 10:00:00', 'Asia/Ho_Chi_Minh'));

        // 1. /booking renders Digital HH:MM + Dual-Column Scroll Wheel (Giờ | Phút), no fixed 9-slot grid, and Summary Card above "Xác nhận lịch hẹn"
        $bookingHtml = $this->get(route('appointments.create'))->assertOk()->getContent();
        $this->assertStringContainsString('data-time-digital-hour', $bookingHtml);
        $this->assertStringContainsString('data-time-digital-minute', $bookingHtml);
        $this->assertStringContainsString('data-wheel-hours', $bookingHtml);
        $this->assertStringContainsString('data-wheel-minutes', $bookingHtml);
        $this->assertStringContainsString('data-picker-apply', $bookingHtml);
        $this->assertStringNotContainsString('data-time-slot=', $bookingHtml);
        $this->assertStringContainsString('data-booking-summary-card', $bookingHtml);
        $this->assertStringContainsString('Thông tin lịch hẹn', $bookingHtml);
        $this->assertStringContainsString('Hạng mục', $bookingHtml);
        $this->assertStringContainsString('Ngày hẹn', $bookingHtml);
        $this->assertStringContainsString('Giờ hẹn', $bookingHtml);
        $this->assertStringContainsString('Xác nhận lịch hẹn', $bookingHtml);

        // 2. Flexible minutes (09:15, 14:05, 18:00) accepted; off-hours (07:55, 18:05) and past VN time today (09:30 when now is 10:00 VN) rejected
        $this->post(route('appointments.store'), [
            'guest_name' => 'Nguyễn Văn Linh Hoạt',
            'guest_phone' => '0901999111',
            'guest_email' => 'linhhoat@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-24 09:15',
        ])->assertRedirect(route('appointments.success'));

        $this->post(route('appointments.store'), [
            'guest_name' => 'Nguyễn Văn Đúng Giờ Đóng',
            'guest_phone' => '0901999112',
            'guest_email' => 'closehour@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-24 18:00',
        ])->assertRedirect(route('appointments.success'));

        // Same-day VN time in future (10:15 when now is 10:00 VN) passes; past VN time today (09:30) fails
        $this->post(route('appointments.store'), [
            'guest_name' => 'Khách Hôm Nay Hợp Lệ',
            'guest_phone' => '0901999113',
            'guest_email' => 'todayok@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-23 10:15',
        ])->assertRedirect(route('appointments.success'));

        $this->post(route('appointments.store'), [
            'guest_name' => 'Khách Quá Giờ Hôm Nay',
            'guest_phone' => '0901999114',
            'guest_email' => 'todaypast@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-23 09:30',
        ])->assertSessionHasErrors('appointment_date');

        $this->post(route('appointments.store'), [
            'guest_name' => 'Khách Ngoài Giờ Sáng',
            'guest_phone' => '0901999115',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-24 07:55',
        ])->assertSessionHasErrors('appointment_date');

        $this->post(route('appointments.store'), [
            'guest_name' => 'Khách Ngoài Giờ Tối',
            'guest_phone' => '0901999116',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => '2026-11-24 18:05',
        ])->assertSessionHasErrors('appointment_date');

        // 3. BookingFormWizard Livewire validates off-hours cleanly & accepts 14:05
        Livewire::test(BookingFormWizard::class)
            ->set('appointmentDate', '2026-11-24 18:05')
            ->assertHasErrors('appointmentDate')
            ->set('appointmentDate', '2026-11-24 14:05')
            ->assertHasNoErrors('appointmentDate');

        // 4. GuestAppointmentManageController reschedule accepts 14:05 and rejects 18:05
        $appointment = Appointment::create([
            'guest_name' => 'Khách Đổi Giờ Linh Hoạt',
            'guest_phone' => '0901999117',
            'guest_email' => 'flex.reschedule@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => Carbon::parse('2026-11-25 10:00:00'),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => '2026-11-26 18:05',
        ])->assertSessionHasErrors('new_appointment_date');

        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => '2026-11-26 14:05',
        ])->assertRedirect();

        $this->assertSame('2026-11-26 14:05', $appointment->fresh()->appointment_date->format('Y-m-d H:i'));
    }

    public function test_phase36_1_all_mailables_serialize_unserialize_and_render_cleanly_with_prevent_lazy_loading_enabled(): void
    {
        Model::preventLazyLoading(true);
        [, $car, $accessory] = $this->createCatalogFixtures();

        $sales = User::factory()->sales()->create([
            'name' => 'Lê Minh Khôi',
            'email' => 'sales.khoi361@bmw.com',
        ]);
        $customer = User::factory()->create([
            'name' => 'Trần Khánh Linh',
            'email' => 'linh.tran361@customer.vn',
        ]);

        $appointment = Appointment::create([
            'user_id' => $customer->id,
            'assigned_to' => $sales->id,
            'guest_name' => $customer->name,
            'guest_phone' => '0909361361',
            'guest_email' => $customer->email,
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 15),
            'status' => AppointmentStatus::Confirmed,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
        ]);

        $order = AccessoryOrder::create([
            'assigned_to' => $sales->id,
            'customer_name' => $customer->name,
            'customer_phone' => '0909361361',
            'customer_email' => $customer->email,
            'customer_address' => '123 Nguyễn Văn Linh, Q.7, TP.HCM',
            'product_id' => $accessory->id,
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_CONFIRMED,
        ]);

        $mailables = [
            new AppointmentConfirmationMail($appointment, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED),
            new AppointmentConfirmationMail($appointment, AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED, $sales->name, $sales->roleLabel()),
            new GuestAppointmentChangeMail($appointment, GuestAppointmentChangeMail::ACTION_RESCHEDULE, '09:00 — 24/11/2026', '10:15 — 25/11/2026', 'Chờ xác nhận', 'Đã xác nhận', 'Đổi giờ sang sáng'),
            new AppointmentCompletedThankYouMail($appointment, AppointmentCompletedThankYouMail::OUTCOME_PURCHASED, 'BMW-CARE-TEST01', '22/12/2026'),
            new AppointmentCompletedThankYouMail($appointment, AppointmentCompletedThankYouMail::OUTCOME_VISITED_NO_PURCHASE),
            new AppointmentReminderMail($appointment),
            new PostTestDriveFollowupMail($appointment),
            new AppointmentAlertMail($appointment),
            new HotLeadAlertMail($appointment),
            new AccessoryOrderAlertMail($order),
            new AccessoryOrderConfirmationMail($order),
            new AccessoryOrderStatusMail($order),
        ];

        foreach ($mailables as $mailable) {
            /** @var Mailable $rehydrated */
            $rehydrated = unserialize(serialize($mailable));

            $envelope = $rehydrated->envelope();
            $this->assertNotEmpty($envelope->subject, $rehydrated::class.' must have a non-empty subject.');

            $rehydrated->attachments();
            $renderedHtml = $rehydrated->render();
            $this->assertNotEmpty($renderedHtml, $rehydrated::class.' rendered empty HTML after queue serialization.');
        }

        // Verify composer.json dev queue listens on notifications,default & mail.php SMTP scheme is safe ('smtp' or 'smtps')
        $composerJson = file_get_contents(base_path('composer.json'));
        $this->assertIsString($composerJson);
        $this->assertStringContainsString('--queue=notifications,default', $composerJson);
        $this->assertContains(config('mail.mailers.smtp.scheme'), ['smtp', 'smtps']);
    }
}
