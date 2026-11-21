<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\NotificationBell;
use App\Livewire\Admin\ServiceBookingTable;
use App\Livewire\BookingFormWizard;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\CustomerPromotionMail;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewAppointmentLeadNotification;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase27ShowroomAutomationAndRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_role_showroom_rbac_login_redirects_notifications_and_self_demotion_guard(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create([
            'name' => 'Giam Doc BMW',
            'email' => 'director.phase27@bmw.com',
            'password' => bcrypt('12345678'),
            'role' => User::ROLE_ADMIN,
        ]);

        $sales = User::factory()->sales()->create([
            'name' => 'Tu Van Ban Hang',
            'email' => 'sales.phase27@bmw.com',
            'password' => bcrypt('12345678'),
        ]);

        $marketing = User::factory()->marketing()->create([
            'name' => 'Chuyen Vien Marketing',
            'email' => 'marketing.phase27@bmw.com',
            'password' => bcrypt('12345678'),
        ]);

        $stranger = User::factory()->create([
            'email' => 'stranger.phase27@gmail.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $category = Category::factory()->create(['name' => 'SAV X Series', 'slug' => 'sav-x-series']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW X7 xDrive40i',
            'slug' => 'bmw-x7-xdrive40i',
            'type' => VehicleType::CAR,
            'price' => 5549000000,
            'is_active' => true,
        ]);
        $appointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Khach RBAC',
            'guest_email' => 'rbac.guest@example.com',
            'guest_phone' => '0901234567',
        ]);

        // 1. Login redirect destinations by role (before any actingAs calls)
        $this->post('/login', [
            'email' => $sales->email,
            'password' => '12345678',
        ])->assertRedirect(route('admin.appointments.index', absolute: false));
        $this->post('/logout');

        $this->post('/login', [
            'email' => $marketing->email,
            'password' => '12345678',
        ])->assertRedirect(route('admin.products.index', absolute: false));
        $this->post('/logout');

        // 2. Allowlist check remains mandatory regardless of DB role
        $this->actingAs($stranger)->get(route('admin.appointments.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($stranger)->get(route('admin.users.index'))->assertForbidden();

        // 3. Role helpers
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue($admin->canAccessCrm());
        $this->assertTrue($admin->canAccessContent());
        $this->assertTrue($admin->canManageSystem());

        $this->assertTrue($sales->isSales());
        $this->assertTrue($sales->canAccessCrm());
        $this->assertFalse($sales->canAccessContent());
        $this->assertFalse($sales->canManageSystem());

        $this->assertTrue($marketing->isMarketing());
        $this->assertFalse($marketing->canAccessCrm());
        $this->assertTrue($marketing->canAccessContent());
        $this->assertFalse($marketing->canManageSystem());

        // 4. Sales route & UI matrix
        $this->actingAs($sales)->get(route('dashboard'))->assertOk();
        $this->actingAs($sales)->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Tư Vấn Bán Hàng')
            ->assertSee('Mở trung tâm thông báo Showroom')
            ->assertSee('Tổng quan CRM')
            ->assertSee('Lịch hẹn')
            ->assertSee('Đơn phụ kiện')
            ->assertSee('Lịch sử trợ lý AI')
            ->assertSee('Khách hàng')
            ->assertDontSee('Bài viết')
            ->assertDontSee('Kho sản phẩm')
            ->assertDontSee('Dòng xe')
            ->assertDontSee('Thiết lập giao diện')
            ->assertDontSee('Nhân sự')
            ->assertDontSee(route('admin.users.index'))
            ->assertDontSee(route('admin.site-settings.edit'));
        $this->actingAs($sales)->get(route('admin.accessory-orders.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.ai-conversations.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee(route('admin.customers.promotions.store'));
        $this->actingAs($sales)->get(route('admin.mail-preview.appointment', $appointment))->assertOk();

        $this->actingAs($sales)->get(route('admin.products.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.categories.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.articles.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.site-settings.edit'))->assertForbidden();
        $this->actingAs($sales)->post(route('admin.customers.promotions.store'), [
            'title' => 'Blocked',
            'content' => 'Blocked content',
            'audience' => 'all',
        ])->assertForbidden();

        // 5. Marketing route & UI matrix
        $this->actingAs($marketing)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Marketing')
            ->assertSee('Kho sản phẩm')
            ->assertSee('Dòng xe')
            ->assertSee('Bài viết')
            ->assertSee('Khách hàng')
            ->assertDontSee('Mở trung tâm thông báo Showroom')
            ->assertDontSee('Tổng quan CRM')
            ->assertDontSee('Lịch hẹn')
            ->assertDontSee('Đơn phụ kiện')
            ->assertDontSee('Lịch sử trợ lý AI')
            ->assertDontSee('Nhân sự')
            ->assertDontSee('Thiết lập giao diện')
            ->assertDontSee(route('admin.appointments.index'))
            ->assertDontSee(route('admin.users.index'));
        $this->actingAs($marketing)->get(route('admin.categories.index'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.articles.index'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee(route('admin.customers.promotions.store'));

        $this->actingAs($marketing)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.appointments.index'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.accessory-orders.index'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.ai-conversations.index'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.site-settings.edit'))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.mail-preview.appointment', $appointment))->assertForbidden();

        // 6. Livewire RBAC enforcement
        Livewire::actingAs($sales)->test(NotificationBell::class)->assertOk();
        Livewire::actingAs($sales)->test(AppointmentTable::class)->assertOk();
        Livewire::actingAs($sales)->test(AccessoryOrderTable::class)->assertOk();

        Livewire::actingAs($marketing)->test(NotificationBell::class)->assertForbidden();
        Livewire::actingAs($marketing)->test(AppointmentTable::class)->assertForbidden();
        Livewire::actingAs($marketing)->test(AccessoryOrderTable::class)->assertForbidden();

        // 7. Admin accesses all areas, sidebar shows both Khách hàng & Nhân sự, creates staff, updates roles, and cannot self-demote
        $this->actingAs($admin)->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Khách hàng')
            ->assertSee(route('admin.customers.index'))
            ->assertSee('Nhân sự')
            ->assertSee(route('admin.users.index'))
            ->assertDontSee('Phase 13');
        $this->actingAs($admin)->get(route('admin.site-settings.edit'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Quản Lý')
            ->assertSee('Tư Vấn Bán Hàng')
            ->assertSee('Marketing');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nhan Vien Sales Moi',
            'email' => 'newsales.phase27@bmw.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'role' => User::ROLE_SALES,
        ])->assertRedirect();

        $newSalesStaff = User::query()->where('email', 'newsales.phase27@bmw.com')->firstOrFail();
        $this->assertSame(User::ROLE_SALES, $newSalesStaff->role);
        $this->assertNotNull($newSalesStaff->email_verified_at);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Nhan Vien Marketing Moi',
            'email' => 'newmkt.phase27@bmw.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'role' => User::ROLE_MARKETING,
        ])->assertRedirect();

        $newMarketingStaff = User::query()->where('email', 'newmkt.phase27@bmw.com')->firstOrFail();
        $this->assertSame(User::ROLE_MARKETING, $newMarketingStaff->role);
        $this->assertNotNull($newMarketingStaff->email_verified_at);

        $this->actingAs($admin)->patch(route('admin.users.update-role', $newSalesStaff), [
            'role' => User::ROLE_MARKETING,
        ])->assertRedirect();
        $this->assertSame(User::ROLE_MARKETING, $newSalesStaff->fresh()->role);

        // Self-demotion to either sales or marketing must be rejected and preserve ROLE_ADMIN
        $this->actingAs($admin)->patch(route('admin.users.update-role', $admin), [
            'role' => User::ROLE_SALES,
        ])->assertRedirect();
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        $this->actingAs($admin)->patch(route('admin.users.update-role', $admin), [
            'role' => User::ROLE_MARKETING,
        ])->assertRedirect();
        $this->assertSame(User::ROLE_ADMIN, $admin->fresh()->role);

        // 8. Hot Lead Notifications & Mails only target Admin + Sales (exclude Marketing)
        $this->post(route('appointments.store'), [
            'guest_name' => 'Vip Hot Lead 27',
            'guest_phone' => '0988777666',
            'guest_email' => 'vip27@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'notes' => 'Tôi muốn mua ngay và đặt cọc trong tuần này.',
        ])->assertRedirect(route('appointments.success'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $admin->id,
            'type' => NewAppointmentLeadNotification::class,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => (new User)->getMorphClass(),
            'notifiable_id' => $sales->id,
            'type' => NewAppointmentLeadNotification::class,
        ]);
        $this->assertSame(
            0,
            DatabaseNotification::query()
                ->where('notifiable_type', (new User)->getMorphClass())
                ->where('notifiable_id', $marketing->id)
                ->count()
        );

        Mail::assertSent(HotLeadAlertMail::class, function (HotLeadAlertMail $mail) use ($admin, $sales, $marketing): bool {
            $mail->assertHasTo($admin->email);
            $mail->assertHasTo($sales->email);

            return ! $mail->hasTo($marketing->email);
        });
    }

    public function test_appointment_ics_attachable_and_dual_attachment_with_brochure_and_signed_url(): void
    {
        $category = Category::factory()->create(['name' => 'M Performance', 'slug' => 'm-performance']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW M4 Competition',
            'slug' => 'bmw-m4-competition',
            'type' => VehicleType::CAR,
            'price' => 6899000000,
            'deposit_amount' => 200000000,
            'specifications' => [
                'Engine' => '3.0L M TwinPower Turbo',
                'Horsepower' => '510 HP',
            ],
            'is_active' => true,
        ]);

        $appointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Tran Calendar VIP',
            'guest_email' => 'calendar.vip@example.com',
            'guest_phone' => '0909333444',
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDays(3)->setTime(10, 30),
            'showroom' => 'BMW Sala Quận 2',
        ]);

        $this->assertInstanceOf(Attachable::class, $appointment);
        $this->assertInstanceOf(Attachment::class, $appointment->toMailAttachment());

        $ics = $appointment->toIcsContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $ics);
        $this->assertStringContainsString('VERSION:2.0', $ics);
        $this->assertStringContainsString('BEGIN:VEVENT', $ics);
        $this->assertStringContainsString('UID:', $ics);
        $this->assertStringContainsString('DTSTART:', $ics);
        $this->assertStringContainsString('DTEND:', $ics);
        $this->assertStringContainsString('SUMMARY:', $ics);
        $this->assertStringContainsString('END:VEVENT', $ics);
        $this->assertStringContainsString('END:VCALENDAR', $ics);

        $mail = new AppointmentConfirmationMail($appointment);
        $headers = $mail->headers();
        $this->assertSame('appointment_confirmation', $headers->text['X-BMW-Mail-Kind'] ?? null);
        $this->assertSame((string) $appointment->id, $headers->text['X-BMW-Appointment-ID'] ?? null);

        $mail->assertSeeInHtml('XÁC NHẬN / QUẢN LÝ LỊCH HẸN');
        $mail->assertSeeInHtml('/appointments/'.$appointment->id.'/manage');
        $mail->assertHasAttachedData($ics, "BMW-Appointment-{$appointment->id}.ics", ['mime' => 'text/calendar']);
        $mail->assertHasAttachedData(
            $mail->renderVehicleBrochureHtml($car),
            "BMW-{$car->slug}-Brochure.html",
            ['mime' => 'text/html']
        );
    }

    public function test_guest_signed_url_portal_confirm_reschedule_cancel_and_rejects_invalid_signatures(): void
    {
        $category = Category::factory()->create(['name' => 'Gran Coupe', 'slug' => 'gran-coupe']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 430i Gran Coupe',
            'slug' => 'bmw-430i-gran-coupe',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $appointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Nguyen Khach Signed',
            'guest_email' => 'signed.guest@example.com',
            'guest_phone' => '0908111222',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2),
            'reminder_sent_at' => now()->subHours(2),
        ]);

        // Unsigned and tampered URLs must fail with 403
        $this->get(route('appointments.guest.manage', $appointment))->assertForbidden();
        $this->get($appointment->temporaryManageUrl().'tampered')->assertForbidden();
        $this->post(route('appointments.guest.update', $appointment), ['action' => 'confirm'])->assertForbidden();

        // Valid signed GET portal renders cleanly with Clean Minimalist Luxury theme + showroom time slots
        $this->get($appointment->temporaryManageUrl())
            ->assertOk()
            ->assertSee($car->name)
            ->assertSee('Nguyen Khach Signed')
            ->assertSee('id="new_date"', false)
            ->assertSee('id="new_time"', false)
            ->assertSee('#F1F5F9', false)
            ->assertSee('name="new_appointment_date"', false);

        // Confirm action
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'confirm',
        ])->assertRedirect()->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Confirmed, $appointment->status);
        $this->assertNotEmpty($appointment->meta_data['guest_confirmed_at'] ?? null);

        // Reschedule form remains open when Confirmed and accepts direct new_appointment_date
        $this->get($appointment->temporaryManageUrl())
            ->assertOk()
            ->assertSee('id="new_date"', false);

        $newDate = now()->addDays(5)->setTime(15, 0);
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_appointment_date' => $newDate->format('Y-m-d H:i'),
            'reason' => 'Bận công tác đột xuất',
        ])->assertRedirect()->assertSessionHas('success');

        $appointment->refresh();
        $this->assertNull($appointment->reminder_sent_at);
        $this->assertSame($newDate->format('Y-m-d H:i'), $appointment->appointment_date->format('Y-m-d H:i'));
        $this->assertSame('Bận công tác đột xuất', $appointment->meta_data['reschedule_reason'] ?? null);

        // Reschedule also accepts split new_date + new_time pair directly (fallback without JS)
        $splitDate = now()->addDays(6);
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $splitDate->format('Y-m-d'),
            'new_time' => '16:30',
            'reason' => 'Đổi sang khung 16:30',
        ])->assertRedirect()->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame($splitDate->format('Y-m-d').' 16:30', $appointment->appointment_date->format('Y-m-d H:i'));
        $this->assertSame('Đổi sang khung 16:30', $appointment->meta_data['reschedule_reason'] ?? null);

        // Cancel action
        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'cancel',
            'reason' => 'Đổi kế hoạch tuần sau',
        ])->assertRedirect()->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->status);
        $this->assertSame('Đổi kế hoạch tuần sau', $appointment->meta_data['cancel_reason'] ?? null);

        // Reschedule is locked once appointment is Cancelled (both UI and Controller)
        $this->get($appointment->temporaryManageUrl())
            ->assertOk()
            ->assertDontSee('id="new_date"', false);

        $this->post($appointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => now()->addDays(7)->format('Y-m-d'),
            'new_time' => '10:00',
        ])->assertRedirect()->assertSessionHas('error');
        $this->assertSame(AppointmentStatus::Cancelled, $appointment->fresh()->status);

        // Reschedule is also locked once appointment is Completed
        $completedAppointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Khach Da Hoan Thanh',
            'guest_email' => 'completed.locked@example.com',
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDay(),
        ]);

        $this->get($completedAppointment->temporaryManageUrl())
            ->assertOk()
            ->assertDontSee('id="new_date"', false);

        $this->post($completedAppointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => now()->addDays(3)->format('Y-m-d'),
            'new_time' => '09:30',
        ])->assertRedirect()->assertSessionHas('error');
    }

    public function test_message_sent_tracking_livewire_resend_and_24h_reminder_command_idempotency(): void
    {
        $admin = User::factory()->create([
            'email' => 'crm.tracker@bmw.com',
            'role' => User::ROLE_ADMIN,
        ]);

        $category = Category::factory()->create(['name' => 'Electric BMW i', 'slug' => 'electric-bmw-i']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW i7 xDrive60',
            'slug' => 'bmw-i7-xdrive60',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        // 1. Livewire AppointmentTable resend triggers real Mailer -> MessageSent listener sets confirmation_email_sent_at
        $untrackedAppointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Nguyen Resend Mail',
            'guest_email' => 'resend.guest@example.com',
            'guest_phone' => '0907555444',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(4),
            'confirmation_email_sent_at' => null,
        ]);

        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('CHƯA GỬI MAIL')
            ->call('resendConfirmationEmail', $untrackedAppointment->id)
            ->assertHasNoErrors();

        $untrackedAppointment->refresh();
        $this->assertNotNull($untrackedAppointment->confirmation_email_sent_at);

        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('ĐÃ GỬI MAIL XÁC NHẬN');

        // 2. 24h Reminder Artisan Command: only sends once for pending/confirmed within (now, +24h]
        Mail::fake();

        $duePending = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Due Pending',
            'guest_email' => 'due.pending@example.com',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addHours(12),
            'reminder_sent_at' => null,
        ]);

        $dueConfirmed = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Due Confirmed',
            'guest_email' => 'due.confirmed@example.com',
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addHours(23),
            'reminder_sent_at' => null,
        ]);

        $tooFar = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Too Far',
            'guest_email' => 'too.far@example.com',
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addHours(30),
            'reminder_sent_at' => null,
        ]);

        $cancelledDue = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Cancelled Due',
            'guest_email' => 'cancelled.due@example.com',
            'status' => AppointmentStatus::Cancelled->value,
            'appointment_date' => now()->addHours(6),
            'reminder_sent_at' => null,
        ]);

        $this->artisan('appointments:send-reminders')->assertSuccessful();

        Mail::assertSent(AppointmentReminderMail::class, 2);
        Mail::assertSent(AppointmentReminderMail::class, function (AppointmentReminderMail $mail) use ($duePending): bool {
            if (! $mail->hasTo('due.pending@example.com')) {
                return false;
            }

            $mail->assertSeeInHtml('XÁC NHẬN / QUẢN LÝ LỊCH HẸN');
            $mail->assertHasAttachedData(
                $duePending->toIcsContent(),
                "BMW-Appointment-{$duePending->id}.ics",
                ['mime' => 'text/calendar']
            );

            return true;
        });
        Mail::assertSent(AppointmentReminderMail::class, fn (AppointmentReminderMail $mail): bool => $mail->hasTo('due.confirmed@example.com'));
        Mail::assertNotSent(AppointmentReminderMail::class, fn (AppointmentReminderMail $mail): bool => $mail->hasTo('too.far@example.com'));
        Mail::assertNotSent(AppointmentReminderMail::class, fn (AppointmentReminderMail $mail): bool => $mail->hasTo('cancelled.due@example.com'));

        $this->assertNotNull($duePending->fresh()->reminder_sent_at);
        $this->assertNotNull($dueConfirmed->fresh()->reminder_sent_at);
        $this->assertNull($tooFar->fresh()->reminder_sent_at);
        $this->assertNull($cancelledDue->fresh()->reminder_sent_at);

        // Second run must be idempotent (0 additional reminder emails)
        $this->artisan('appointments:send-reminders')->assertSuccessful();
        Mail::assertSentTimes(AppointmentReminderMail::class, 2);
    }

    public function test_promotion_campaign_dispatch_filters_audience_and_queues_personalized_emails(): void
    {
        $marketing = User::factory()->marketing()->create(['email' => 'promo.mkt@bmw.com']);
        $sales = User::factory()->sales()->create(['email' => 'promo.sales@bmw.com']);

        $carCat = Category::factory()->create(['name' => 'Luxury SUV', 'slug' => 'luxury-suv']);

        $bmwX5 = Product::factory()->for($carCat)->create([
            'name' => 'BMW X5 xDrive40i',
            'slug' => 'bmw-x5-xdrive40i',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);
        $bmw330i = Product::factory()->for($carCat)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        // VIP A completed BMW X5 twice (should be deduplicated!)
        Appointment::factory()->for($bmwX5, 'product')->guest()->create([
            'guest_name' => 'VIP A',
            'guest_email' => 'vip.a@example.com',
            'guest_phone' => '0901000001',
            'status' => AppointmentStatus::Completed->value,
        ]);
        Appointment::factory()->for($bmwX5, 'product')->guest()->create([
            'guest_name' => 'VIP A',
            'guest_email' => 'vip.a@example.com',
            'guest_phone' => '0901000001',
            'status' => AppointmentStatus::Completed->value,
        ]);

        // Pending B for BMW X5
        Appointment::factory()->for($bmwX5, 'product')->guest()->create([
            'guest_name' => 'Pending B',
            'guest_email' => 'pending.b@example.com',
            'guest_phone' => '0901000002',
            'status' => AppointmentStatus::Pending->value,
        ]);

        // Completed C for BMW 330i
        Appointment::factory()->for($bmw330i, 'product')->guest()->create([
            'guest_name' => 'Completed C',
            'guest_email' => 'vip.c@example.com',
            'guest_phone' => '0901000003',
            'status' => AppointmentStatus::Completed->value,
        ]);

        // Cancelled Appointment D (must NOT be included in 'completed' audience)
        Appointment::factory()->for($bmw330i, 'product')->guest()->create([
            'guest_name' => 'Cancelled D',
            'guest_email' => 'cancelled.d@example.com',
            'guest_phone' => '0901000004',
            'status' => AppointmentStatus::Cancelled->value,
        ]);

        // Sales cannot launch promotion campaigns
        $this->actingAs($sales)->post(route('admin.customers.promotions.store'), [
            'title' => 'No Access',
            'content' => 'Blocked',
            'audience' => 'all',
        ])->assertForbidden();

        // 1. Filtered by product + only_completed
        Mail::fake();

        $this->actingAs($marketing)->post(route('admin.customers.promotions.store'), [
            'title' => 'Ưu đãi bảo dưỡng BMW X5',
            'content' => 'Miễn phí kiểm tra 20 hạng mục trong tháng này.',
            'audience' => 'by_product',
            'product_id' => $bmwX5->id,
            'only_completed' => '1',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertSent(CustomerPromotionMail::class, 1);
        Mail::assertSent(CustomerPromotionMail::class, function (CustomerPromotionMail $mail) use ($bmwX5): bool {
            $mail->assertHasTo('vip.a@example.com');
            $mail->assertSeeInHtml('VIP A');
            $mail->assertSeeInHtml($bmwX5->name);
            $mail->assertSeeInHtml('Miễn phí kiểm tra 20 hạng mục trong tháng này.');

            return str_contains((string) $mail->envelope()->subject, 'Ưu đãi bảo dưỡng BMW X5');
        });
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('pending.b@example.com'));
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('vip.c@example.com'));

        // 2. Audience 'completed' sends deduplicated personalized mails to VIP A and Completed C only
        Mail::fake();

        $this->actingAs($marketing)->post(route('admin.customers.promotions.store'), [
            'title' => 'Tri ân khách hàng thân thiết BMW',
            'content' => 'Tặng voucher dịch vụ trị giá 5.000.000 VNĐ.',
            'audience' => 'completed',
        ])->assertRedirect()->assertSessionHas('success');

        Mail::assertSent(CustomerPromotionMail::class, 2);
        Mail::assertSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('vip.a@example.com'));
        Mail::assertSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('vip.c@example.com'));
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('pending.b@example.com'));
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('cancelled.d@example.com'));
    }

    public function test_booking_rate_limiter_defer_sql_budget_and_comma_separated_showroom_admin_emails(): void
    {
        Mail::fake();
        Cache::flush();

        config([
            'mail.showroom_admin' => 'director1@bmw.com, director2@gmail.com',
            'mail.booking_rate_limit_per_minute' => 5,
        ]);

        $sales = User::factory()->sales()->create(['email' => 'budget.sales@bmw.com']);
        $carCat = Category::factory()->create(['name' => 'Sedan 7', 'slug' => 'sedan-7']);
        $accCat = Category::factory()->create(['name' => 'Phụ kiện 7', 'slug' => 'phu-kien-7']);

        $car = Product::factory()->for($carCat)->create([
            'name' => 'BMW 735i Pure Excellence',
            'slug' => 'bmw-735i-pure-excellence',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);
        $accessory = Product::factory()->for($accCat)->create([
            'name' => 'Chìa khóa BMW Display Key',
            'slug' => 'chia-khoa-bmw-display-key',
            'type' => VehicleType::ACCESSORY,
            'price' => 15000000,
            'is_active' => true,
        ]);

        // 5 consecutive bookings pass from the test client IP, dispatching defer() notifications/mails
        for ($i = 1; $i <= 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.27.0.99'])
                ->post(route('appointments.store'), [
                    'guest_name' => "Booking Client {$i}",
                    'guest_phone' => "090500000{$i}",
                    'guest_email' => "client{$i}@example.com",
                    'product_id' => $car->id,
                    'type' => AppointmentType::TestDrive->value,
                    'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
                ])
                ->assertRedirect(route('appointments.success'));
        }

        // Comma-separated MAIL_SHOWROOM_ADMIN receives AppointmentAlertMail
        Mail::assertQueued(AppointmentAlertMail::class, function (AppointmentAlertMail $mail): bool {
            return $mail->hasTo('director1@bmw.com') && $mail->hasTo('director2@gmail.com');
        });

        // 6th booking within 1 minute from same IP is throttled with HTTP 429
        $this->withServerVariables(['REMOTE_ADDR' => '10.27.0.99'])
            ->post(route('appointments.store'), [
                'guest_name' => 'Booking Client 6',
                'guest_phone' => '0905000006',
                'guest_email' => 'client6@example.com',
                'product_id' => $car->id,
                'type' => AppointmentType::TestDrive->value,
                'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            ])
            ->assertStatus(429);

        // Comma-separated MAIL_SHOWROOM_ADMIN receives AccessoryOrderAlertMail
        $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Buyer Comma Admin',
            'customer_phone' => '0905999888',
            'customer_address' => '12 Nguyễn Huệ, Quận 1',
            'customer_email' => 'buyer.comma@example.com',
            'quantity' => 1,
        ])->assertRedirect(route('accessory-orders.create', $accessory->slug));

        Mail::assertQueued(AccessoryOrderAlertMail::class, function (AccessoryOrderAlertMail $mail): bool {
            return $mail->hasTo('director1@bmw.com') && $mail->hasTo('director2@gmail.com');
        });

        // Cold cache SQL budget check on GET /admin/appointments <= 8 queries
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($sales)
            ->get(route('admin.appointments.index'))
            ->assertOk()
            ->assertSee('Booking Client 5');

        $coldQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            8,
            count($coldQueries),
            'Expected <= 8 SQL queries on /admin/appointments even when cache is cold.'
        );
    }

    public function test_customer_list_filters_live_counter_product_override_and_completed_vehicle_priority(): void
    {
        $marketing = User::factory()->marketing()->create(['email' => 'filter.mkt@bmw.com']);

        $sedanCat = Category::factory()->create(['name' => 'Sedan 3', 'slug' => 'sedan-3-promo']);
        $motoCat = Category::factory()->create(['name' => 'BMW Motorrad', 'slug' => 'bmw-motorrad-promo']);

        $bmw330i = Product::factory()->for($sedanCat)->create([
            'name' => 'BMW 330i',
            'slug' => 'bmw-330i-filter-test',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $bmwG310r = Product::factory()->for($motoCat)->create([
            'name' => 'BMW G310R',
            'slug' => 'bmw-g310r-filter-test',
            'type' => VehicleType::MOTORBIKE,
            'is_active' => true,
        ]);

        // Dual customer has an older COMPLETED BMW G310R and a newer PENDING BMW 330i
        Appointment::factory()->for($bmwG310r, 'product')->guest()->create([
            'guest_name' => 'Khach Dual Xe',
            'guest_email' => 'dual.priority@example.com',
            'guest_phone' => '0902000111',
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(10),
        ]);

        Appointment::factory()->for($bmw330i, 'product')->guest()->create([
            'guest_name' => 'Khach Dual Xe',
            'guest_email' => 'dual.priority@example.com',
            'guest_phone' => '0902000111',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->subDay(),
        ]);

        // Second customer only has PENDING BMW 330i
        Appointment::factory()->for($bmw330i, 'product')->guest()->create([
            'guest_name' => 'Khach Pending 330i',
            'guest_email' => 'pending.only@example.com',
            'guest_phone' => '0902000222',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->subHours(5),
        ]);

        // 1. Customer list renders custom-select filters + 2-column Live Preview campaign UI
        $indexResponse = $this->actingAs($marketing)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('role="listbox"', false)
            ->assertSee('id="customer-filter-status"', false)
            ->assertSee('id="customer-filter-product"', false)
            ->assertSee('data-testid="promotion-matched-counter"', false)
            ->assertSee('Sẽ gửi tới')
            ->assertSee('khách hàng phù hợp')
            ->assertSee('Live Preview')
            ->assertSee('PHÁT CHIẾN DỊCH NGAY')
            ->assertSee('Khach Dual Xe')
            ->assertSee('Khach Pending 330i');

        $tuples = $indexResponse->viewData('promotionAudienceTuples');
        $dualFirstRecord = collect($tuples)->firstWhere('email', 'dual.priority@example.com');
        $this->assertNotNull($dualFirstRecord);
        $this->assertSame('BMW G310R', $dualFirstRecord['vehicle']);

        // Filter by status=completed excludes Khach Pending 330i from Customer Table
        $completedFilterResponse = $this->actingAs($marketing)
            ->get(route('admin.customers.index', ['status' => AppointmentStatus::Completed->value]))
            ->assertOk();
        $this->assertSame(
            ['Khach Dual Xe'],
            $completedFilterResponse->viewData('customers')->pluck('name')->all()
        );

        // Filter by product_id=BMW G310R excludes Khach Pending 330i from Customer Table
        $productFilterResponse = $this->actingAs($marketing)
            ->get(route('admin.customers.index', ['product_id' => $bmwG310r->id]))
            ->assertOk();
        $this->assertSame(
            ['Khach Dual Xe'],
            $productFilterResponse->viewData('customers')->pluck('name')->all()
        );

        // 2. Empty string product_id ("") normalizes smoothly to null & deduplication prioritizes completed BMW G310R
        Mail::fake();

        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Ưu đãi ưu tiên xe hoàn thành',
                'content' => 'Nội dung cá nhân hóa theo xe đã hoàn thành.',
                'audience' => 'all',
                'product_id' => '',
            ])
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success');

        Mail::assertSent(CustomerPromotionMail::class, 2);
        Mail::assertSent(CustomerPromotionMail::class, function (CustomerPromotionMail $mail): bool {
            if (! $mail->hasTo('dual.priority@example.com')) {
                return false;
            }

            $mail->assertSeeInHtml('BMW G310R');
            $mail->assertDontSeeInHtml('BMW 330i');

            return true;
        });

        // 3. Selecting product_id ALWAYS filters by that product even when audience='all'
        Mail::fake();

        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Ưu đãi riêng Motorrad G310R',
                'content' => 'Gói bảo dưỡng chuyên biệt BMW G310R.',
                'audience' => 'all',
                'product_id' => $bmwG310r->id,
            ])
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success');

        Mail::assertSent(CustomerPromotionMail::class, 1);
        Mail::assertSent(CustomerPromotionMail::class, function (CustomerPromotionMail $mail): bool {
            $mail->assertHasTo('dual.priority@example.com');
            $mail->assertSeeInHtml('BMW G310R');

            return true;
        });
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $mail): bool => $mail->hasTo('pending.only@example.com'));

        // 4. Selecting product_id=BMW 330i with audience='completed' yields 0 completed recipients
        Mail::fake();

        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Ưu đãi Sedan đã giao',
                'content' => 'Không có khách hoàn thành BMW 330i.',
                'audience' => 'completed',
                'product_id' => $bmw330i->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
    }

    public function test_vehicle_care_services_booking_flow_admin_service_bookings_and_concierge_email_layout(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create([
            'name' => 'Giam Doc Dich Vu',
            'email' => 'service.admin@bmw.com',
            'role' => User::ROLE_ADMIN,
        ]);
        $sales = User::factory()->sales()->create([
            'name' => 'Co Van Dich Vu',
            'email' => 'service.sales@bmw.com',
        ]);
        $marketing = User::factory()->marketing()->create([
            'name' => 'Marketing Dich Vu',
            'email' => 'service.marketing@bmw.com',
        ]);

        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-service']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport-service',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        // 1. Public /services and /dich-vu alias return 200 with 4 official packages and booking links
        foreach ([route('services.index'), route('services.dich-vu')] as $serviceUrl) {
            $this->get($serviceUrl)
                ->assertOk()
                ->assertSee('Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)')
                ->assertSee('Phủ Ceramic &amp; Hiệu chỉnh bề mặt sơn (BMW Paint Protection)', false)
                ->assertSee('Chăm sóc nội thất da Merino &amp; Vệ sinh khoang máy chuyên sâu', false)
                ->assertSee('Kiểm tra tổng quát &amp; Chẩn đoán điện tử ISTA chính hãng', false)
                ->assertSee('bmw-checklist', false)
                ->assertSee('ĐẶT LỊCH CHĂM SÓC XE');
        }

        // 2. BookingFormWizard dynamically shows #service_package_selector when switching to service type on /booking
        Livewire::test(BookingFormWizard::class)
            ->assertDontSee('id="service_package_selector"', false)
            ->set('type', AppointmentType::Maintenance->value)
            ->assertSee('id="service_package_selector"', false);

        // 3. POST /booking with type=service & service_package persists into meta_data['service_package']
        $selectedPackage = 'Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)';
        $bookingDate = now()->addDays(4)->setTime(10, 30)->format('Y-m-d H:i');
        $this->post(route('appointments.store'), [
            'guest_name' => 'Đặng Hoàng Dịch Vụ',
            'guest_phone' => '0988777666',
            'guest_email' => 'service.vip@example.com',
            'product_id' => $car->id,
            'customer_car_model' => 'BMW 330i M Sport',
            'license_plate' => '51K-999.99',
            'appointment_date' => $bookingDate,
            'type' => 'service',
            'service_package' => $selectedPackage,
            'notes' => 'Cần phủ Ceramic gói 9H và đánh bóng hiệu chỉnh sơn.',
        ])->assertRedirect(route('appointments.success'));

        $serviceAppointment = Appointment::query()
            ->where('guest_email', 'service.vip@example.com')
            ->firstOrFail();

        $this->assertSame(AppointmentType::Maintenance, $serviceAppointment->type);
        $this->assertSame($selectedPackage, data_get($serviceAppointment->meta_data, 'service_package'));
        $this->assertSame($selectedPackage, $serviceAppointment->servicePackageLabel());

        // Create a non-service test_drive appointment to ensure /admin/service-bookings filters only service types
        $testDriveAppointment = Appointment::query()->create([
            'guest_name' => 'Khách Lái Thử Không Hiện Ở Dịch Vụ',
            'guest_phone' => '0911000999',
            'guest_email' => 'testdrive.only@example.com',
            'product_id' => $car->id,
            'appointment_date' => now()->addDays(5)->setTime(14, 0),
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Pending->value,
            'lead_priority' => 'hot',
            'ai_lead_note' => 'Khách muốn chốt xe ngay tuần này.',
            'ai_chat_summary' => 'Khách hỏi chi tiết giá lăn bánh và lịch giao xe.',
        ]);

        // 4. /admin/service-bookings RBAC + cold cache <= 8 SQL queries + Livewire status update
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)
            ->get(route('admin.service-bookings.index'))
            ->assertOk()
            ->assertSee('Đặng Hoàng Dịch Vụ')
            ->assertSee('Phủ Ceramic &amp; Hiệu chỉnh bề mặt sơn (BMW Paint Protection)', false);

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(8, $queryCount, "Expected <= 8 queries on cold cache for /admin/service-bookings, got {$queryCount}");

        $this->actingAs($sales)
            ->get(route('admin.service-bookings.index'))
            ->assertOk()
            ->assertSee('Đặng Hoàng Dịch Vụ');

        $this->actingAs($marketing)
            ->get(route('admin.service-bookings.index'))
            ->assertForbidden();

        Livewire::actingAs($sales)
            ->test(ServiceBookingTable::class)
            ->assertSee('Đặng Hoàng Dịch Vụ')
            ->assertDontSee('Khách Lái Thử Không Hiện Ở Dịch Vụ')
            ->call('updateStatus', $serviceAppointment->id, AppointmentStatus::Confirmed->value);

        $this->assertSame(AppointmentStatus::Confirmed, $serviceAppointment->fresh()->status);

        // 5. Verify Clean Minimalist Luxury Email Layout (sans-serif, #F1F5F9, #FFFFFF, no monospace/Courier)
        $mailables = [
            new AppointmentConfirmationMail($serviceAppointment->fresh('product')),
            new AppointmentReminderMail($serviceAppointment->fresh('product')),
            new CustomerPromotionMail(
                customerName: 'Đặng Hoàng Dịch Vụ',
                vehicleName: $car->name,
                offerTitle: 'Ưu đãi Bảo dưỡng Mùa Hè',
                offerContent: 'Giảm 20% gói phủ Ceramic chính hãng.',
            ),
            new HotLeadAlertMail($testDriveAppointment->fresh('product')),
        ];

        foreach ($mailables as $mailable) {
            $html = $mailable->render();
            $this->assertStringContainsString('#F1F5F9', $html);
            $this->assertStringContainsString('#FFFFFF', $html);
            $this->assertStringContainsString('BMW SHOWROOM', $html);
            $this->assertStringContainsString("font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;", $html);
            $this->assertStringNotContainsString('monospace', strtolower($html));
            $this->assertStringNotContainsString('courier', strtolower($html));
        }
    }
}
