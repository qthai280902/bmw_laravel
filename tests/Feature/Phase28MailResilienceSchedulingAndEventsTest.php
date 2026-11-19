<?php

namespace Tests\Feature;

use App\Console\Commands\CleanupAndFollowupAppointmentsCommand;
use App\Console\Commands\SendShowroomDailySummaryCommand;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Events\CatalogEmbeddingSyncRequested;
use App\Events\PromotionCampaignDispatched;
use App\Listeners\RecordAppointmentMailSent;
use App\Listeners\SendPromotionCampaignEmails;
use App\Listeners\SyncCatalogEmbeddingListener;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\NotificationBell;
use App\Livewire\Admin\ServiceBookingTable;
use App\Livewire\BookingFormWizard;
use App\Livewire\CatalogBrowser;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\Admin\ShowroomDailySummaryMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Mail\CustomerPromotionMail;
use App\Models\AccessoryOrder;
use App\Models\AdminActivityLog;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\MailDeliveryFailureNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use RuntimeException;
use Symfony\Component\Mailer\Envelope as SymfonyEnvelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

class Phase28MailResilienceSchedulingAndEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_resilience_failover_config_fallback_alert_reply_to_plaintext_inline_embed_and_hot_lead_priority(): void
    {
        Cache::flush();

        // 1. Failover & Reply-To configuration in config/mail.php
        $failoverConfig = config('mail.mailers.failover');
        $this->assertSame('failover', $failoverConfig['transport'] ?? null);
        $this->assertSame(['smtp', 'log'], $failoverConfig['mailers'] ?? null);
        $this->assertSame(60, $failoverConfig['retry_after'] ?? null);
        $this->assertNotEmpty(config('mail.reply_to.address'));
        $this->assertNotEmpty(config('mail.reply_to.name'));

        config([
            'mail.showroom_admin' => 'sales.lead@bmw.com',
            'mail.showroom_admins' => ['sales.lead@bmw.com'],
            'mail.reply_to.address' => 'sales.lead@bmw.com',
            'mail.reply_to.name' => 'BMW Showroom Tư Vấn',
        ]);

        $admin = User::factory()->create(['email' => 'director.p28@bmw.com', 'role' => User::ROLE_ADMIN]);
        $sales = User::factory()->sales()->create(['email' => 'sales.p28@bmw.com']);
        $marketing = User::factory()->marketing()->create(['email' => 'marketing.p28@bmw.com']);

        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-p28']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport-p28',
            'type' => VehicleType::CAR,
            'price' => 3289000000,
            'is_active' => true,
        ]);

        $appointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Nguyen Mail Resilience',
            'guest_email' => 'resilience.guest@example.com',
            'guest_phone' => '0908111222',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Pending->value,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'appointment_date' => now()->addDays(2),
        ]);

        // 2. Reply-To, plain-text, and inline embedData/cid in Confirmation, Reminder & Promotion mails
        $confirmMail = new AppointmentConfirmationMail($appointment);
        $confirmMail->assertHasReplyTo('sales.lead@bmw.com', 'BMW Showroom Tư Vấn');
        $confirmMail->assertSeeInText('Nguyen Mail Resilience');
        $confirmMail->assertSeeInText('BMW 530i M Sport');

        $sentConfirm = Mail::mailer('array')->to('resilience.guest@example.com')->sendNow(new AppointmentConfirmationMail($appointment));
        $this->assertNotNull($sentConfirm);
        $this->assertStringContainsString('cid:', (string) $sentConfirm->getOriginalMessage()->getHtmlBody());

        $reminderMail = new AppointmentReminderMail($appointment);
        $reminderMail->assertHasReplyTo('sales.lead@bmw.com', 'BMW Showroom Tư Vấn');
        $reminderMail->assertSeeInText('Nguyen Mail Resilience');

        $sentReminder = Mail::mailer('array')->to('resilience.guest@example.com')->sendNow(new AppointmentReminderMail($appointment));
        $this->assertNotNull($sentReminder);
        $this->assertStringContainsString('cid:', (string) $sentReminder->getOriginalMessage()->getHtmlBody());

        $promoMail = new CustomerPromotionMail(
            customerName: 'Nguyen Mail Resilience',
            vehicleName: 'BMW 530i M Sport',
            offerTitle: 'Ưu đãi Độc Quyền 5 Series',
            offerContent: 'Tặng 2 năm bảo dưỡng chính hãng tại BMW Showroom.',
        );
        $promoMail->assertHasReplyTo('sales.lead@bmw.com', 'BMW Showroom Tư Vấn');
        $promoMail->assertSeeInText('Ưu đãi Độc Quyền 5 Series');
        $promoMail->assertSeeInText('Nguyen Mail Resilience');

        // Browser preview route must remain 200 OK even without $message embed object
        $this->actingAs($admin)
            ->get(route('admin.mail-preview.appointment', $appointment))
            ->assertOk()
            ->assertSee('BMW 530i M Sport');

        // 3. HotLeadAlertMail sets PRIORITY_HIGH via envelope()->using
        $hotLeadMail = new HotLeadAlertMail($appointment);
        $envelope = $hotLeadMail->envelope();
        $symfonyEmail = new Email;
        foreach ($envelope->using as $callback) {
            $callback($symfonyEmail);
        }
        $this->assertSame(Email::PRIORITY_HIGH, $symfonyEmail->getPriority());

        // 4. RecordAppointmentMailSent safely handles array/Mail::fake AND alerts admin+sales when failover drops to log
        $rawEmail = (new Email)
            ->from(new SymfonyAddress('no-reply@bmw.com', 'BMW'))
            ->to(new SymfonyAddress('resilience.guest@example.com'))
            ->subject('[BMW Showroom] Test Failover')
            ->text('Failover fallback test message.');
        $sentMessage = new SentMessage(new SymfonySentMessage(
            $rawEmail,
            new SymfonyEnvelope(
                new SymfonyAddress('no-reply@bmw.com'),
                [new SymfonyAddress('resilience.guest@example.com')]
            )
        ));

        // Under default array mailer -> no failure alert
        config(['mail.default' => 'array']);
        app(RecordAppointmentMailSent::class)->handle(new MessageSent($sentMessage, []));
        $this->assertSame(0, DatabaseNotification::query()->where('type', MailDeliveryFailureNotification::class)->count());

        // Under failover falling back to log -> notifies admin & sales, NOT marketing
        config(['mail.default' => 'failover']);
        app(RecordAppointmentMailSent::class)->handle(
            new MessageSent($sentMessage, ['failover_fallback_transport' => 'log'])
        );

        $notifiedIds = DatabaseNotification::query()
            ->where('type', MailDeliveryFailureNotification::class)
            ->pluck('notifiable_id')
            ->all();

        $this->assertContains($admin->id, $notifiedIds);
        $this->assertContains($sales->id, $notifiedIds);
        $this->assertNotContains($marketing->id, $notifiedIds);
    }

    public function test_cleanup_and_followup_command_and_post_test_drive_installment_email(): void
    {
        Mail::fake();
        Cache::flush();

        $sales = User::factory()->sales()->create(['email' => 'sales.followup@bmw.com']);
        $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav-p28']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW X5 xDrive40i',
            'slug' => 'bmw-x5-xdrive40i-p28',
            'type' => VehicleType::CAR,
            'price' => 4000000000,
            'is_active' => true,
        ]);

        // Overdue pending (> 24h ago) -> should be auto-cancelled
        $overduePending = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Khach Qua Han',
            'guest_email' => 'overdue@example.com',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->subHours(30),
        ]);

        // Recent pending (only 5h ago) -> must stay pending
        $recentPending = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Khach Moi Dat',
            'guest_email' => 'recent@example.com',
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->subHours(5),
        ]);

        // Completed appointment without followup_email_sent_at -> should queue followup once
        $completedNeedingFollowup = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Tran Hau Mai',
            'guest_email' => 'followup.vip@example.com',
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subHours(3),
            'followup_email_sent_at' => null,
        ]);

        $this->assertInstanceOf(Isolatable::class, app(CleanupAndFollowupAppointmentsCommand::class));

        $this->artisan('appointments:cleanup-and-followup')->assertSuccessful();

        $overduePending->refresh();
        $recentPending->refresh();
        $completedNeedingFollowup->refresh();

        $this->assertSame(AppointmentStatus::Cancelled, $overduePending->status);
        $this->assertSame(
            'Tự động hủy do quá hạn 24 giờ chưa xác nhận',
            data_get($overduePending->meta_data, 'auto_cancelled_reason')
        );
        $this->assertSame(AppointmentStatus::Pending, $recentPending->status);
        $this->assertNotNull($completedNeedingFollowup->followup_email_sent_at);

        Mail::assertQueued(PostTestDriveFollowupMail::class, 1);
        Mail::assertQueued(PostTestDriveFollowupMail::class, function (PostTestDriveFollowupMail $mail): bool {
            $mail->assertHasTo('followup.vip@example.com');
            $mail->assertSeeInText('BMW X5 xDrive40i');
            $mail->assertSeeInText('4.000.000.000 VNĐ');
            $mail->assertSeeInText('800.000.000 VNĐ');
            $mail->assertSeeInText('3.200.000.000 VNĐ');
            $mail->assertSeeInText('60 tháng');
            $mail->assertSeeInText('8.5%/năm');

            $html = $mail->render();
            $this->assertStringContainsString('#0B0E14', $html);
            $this->assertStringContainsString('#121620', $html);
            $this->assertStringContainsString('CONCIERGE &amp; CLIENT RELATIONS', $html);
            $this->assertStringContainsString('BẢNG ƯỚC TÍNH TÀI CHÍNH / TRẢ GÓP THAM KHẢO', $html);
            $this->assertStringNotContainsString('monospace', strtolower($html));
            $this->assertStringNotContainsString('courier', strtolower($html));

            return $mail->delay !== null;
        });

        // Running command a second time must NOT re-queue followup email
        $this->artisan('appointments:cleanup-and-followup')->assertSuccessful();
        Mail::assertQueued(PostTestDriveFollowupMail::class, 1);

        // Transitioning status to completed in Livewire AppointmentTable also queues followup once
        $livewireAppointment = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Le Livewire Complete',
            'guest_email' => 'livewire.complete@example.com',
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDay(),
            'followup_email_sent_at' => null,
        ]);

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('updateStatus', $livewireAppointment->id, AppointmentStatus::Completed->value)
            ->assertHasNoErrors();

        $this->assertNotNull($livewireAppointment->fresh()->followup_email_sent_at);
        Mail::assertQueued(PostTestDriveFollowupMail::class, 2);
    }

    public function test_showroom_daily_summary_command_and_console_schedule_registry(): void
    {
        Mail::fake();
        Cache::flush();

        config([
            'mail.showroom_admin' => 'gm@bmw.com,ops@bmw.com',
            'mail.showroom_admins' => ['gm@bmw.com', 'ops@bmw.com'],
        ]);

        $category = Category::factory()->create(['name' => 'Lifestyle', 'slug' => 'lifestyle-p28']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW M4 Competition',
            'slug' => 'bmw-m4-p28',
            'type' => VehicleType::CAR,
            'price' => 6500000000,
            'is_active' => true,
        ]);
        $accessory = Product::factory()->for($category)->create([
            'name' => 'Mũ BMW M Motorsport',
            'slug' => 'mu-bmw-m-p28',
            'type' => VehicleType::ACCESSORY,
            'price' => 2500000,
            'is_active' => true,
        ]);

        Appointment::factory()->for($car, 'product')->guest()->create([
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'notes' => 'Tôi muốn mua ngay trong tuần này và đặt cọc xe.',
        ]);
        Appointment::factory()->for($car, 'product')->guest()->create([
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Pending->value,
            'lead_priority' => Appointment::LEAD_PRIORITY_NORMAL,
        ]);
        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khach Phu Kien Ngay',
            'customer_phone' => '0909123123',
            'customer_address' => 'Quận 7, TP.HCM',
            'customer_email' => 'acc.day@example.com',
            'quantity' => 3,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $this->assertInstanceOf(Isolatable::class, app(SendShowroomDailySummaryCommand::class));

        $this->artisan('showroom:daily-summary')->assertSuccessful();

        Mail::assertSent(ShowroomDailySummaryMail::class, function (ShowroomDailySummaryMail $mail): bool {
            $mail->assertHasTo('gm@bmw.com');
            $mail->assertHasTo('ops@bmw.com');
            $mail->assertSeeInText('7.500.000 VNĐ');

            $html = $mail->render();
            $this->assertStringContainsString('#0B0E14', $html);
            $this->assertStringContainsString('#121620', $html);
            $this->assertStringNotContainsString('monospace', strtolower($html));

            return $mail->summary['total_appointments'] === 2
                && $mail->summary['hot_leads'] === 1
                && $mail->summary['service_bookings'] === 1
                && $mail->summary['confirmed_or_completed'] === 1
                && $mail->summary['accessory_orders_count'] === 1
                && $mail->summary['accessory_orders_value'] === 7500000;
        });

        // Verify all required tasks are registered in routes/console.php
        $schedule = app(Schedule::class);
        $events = collect($schedule->events());

        $descriptionsAndCommands = $events->map(fn ($event): string => trim((string) ($event->command ?? '').' '.(string) ($event->description ?? '')))->implode("\n");

        $this->assertStringContainsString('appointments:send-reminders', $descriptionsAndCommands);
        $this->assertStringContainsString('appointments:cleanup-and-followup', $descriptionsAndCommands);
        $this->assertStringContainsString('showroom:daily-summary', $descriptionsAndCommands);
        $this->assertStringContainsString('ai:sync-embeddings', $descriptionsAndCommands);
        $this->assertStringContainsString('prune-read-admin-notifications', $descriptionsAndCommands);
        $this->assertStringContainsString('queue:prune-failed', $descriptionsAndCommands);
    }

    public function test_encrypted_promotion_listener_backoff_failure_alert_and_debounced_catalog_embedding_sync(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['email' => 'boss.p28@bmw.com', 'role' => User::ROLE_ADMIN]);
        $sales = User::factory()->sales()->create(['email' => 'sales.alert.p28@bmw.com']);
        $marketing = User::factory()->marketing()->create(['email' => 'mkt.alert.p28@bmw.com']);

        // 1. SendPromotionCampaignEmails implements ShouldBeEncrypted, backoff [10, 60, 180], failed() notifies admin+sales only
        $promoListener = app(SendPromotionCampaignEmails::class);
        $this->assertInstanceOf(ShouldBeEncrypted::class, $promoListener);
        $this->assertSame(3, $promoListener->tries);
        $this->assertSame([10, 60, 180], $promoListener->backoff());

        $promoEvent = new PromotionCampaignDispatched(
            campaignId: 'camp-fail-p28',
            offerTitle: 'Ưu đãi VIP Tháng 11',
            offerContent: 'Miễn phí kiểm tra xe toàn diện.',
            recipients: [
                ['email' => 'vip1@example.com', 'name' => 'VIP 1', 'vehicle_name' => 'BMW 740i'],
            ],
        );

        $promoListener->failed($promoEvent, new RuntimeException('SMTP 550 daily sending quota exceeded'));

        $notifiedIds = DatabaseNotification::query()
            ->where('type', MailDeliveryFailureNotification::class)
            ->pluck('notifiable_id')
            ->all();

        $this->assertContains($admin->id, $notifiedIds);
        $this->assertContains($sales->id, $notifiedIds);
        $this->assertNotContains($marketing->id, $notifiedIds);

        Livewire::actingAs($sales)
            ->test(NotificationBell::class)
            ->assertSee('CẢNH BÁO MAIL')
            ->assertSee('Ưu đãi VIP Tháng 11');

        // 2. SyncCatalogEmbeddingListener debounces consecutive saves within 30s and respects maxWait 120s
        config([
            'showroom_ai.embeddings.enabled' => true,
            'showroom_ai.embeddings.accounts_list' => 'acc-p28:tok-p28',
            'showroom_ai.embeddings.numbered_accounts' => [],
            'showroom_ai.embeddings.account_id' => null,
            'showroom_ai.embeddings.api_token' => null,
        ]);

        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/*' => Http::response([
                'success' => true,
                'result' => ['data' => [[0.2, 0.5, 0.8]]],
            ], 200),
        ]);

        $category = Category::factory()->create(['name' => 'Coupe', 'slug' => 'coupe-p28']);
        $product = Product::withoutEvents(fn () => Product::factory()->for($category)->create([
            'name' => 'BMW 430i Gran Coupe',
            'slug' => 'bmw-430i-p28',
            'type' => VehicleType::CAR,
            'price' => 2999000000,
            'is_active' => true,
        ]));

        $syncListener = app(SyncCatalogEmbeddingListener::class);

        $firstEvent = new CatalogEmbeddingSyncRequested('product', (int) $product->id);
        $secondEvent = new CatalogEmbeddingSyncRequested('product', (int) $product->id);

        $this->assertSame("product:{$product->id}", $syncListener->debounceId($secondEvent));
        $this->assertSame(30, $syncListener->withDelay($secondEvent));

        // Stale first event must be skipped; only latest second event hits Cloudflare once
        $syncListener->handle($firstEvent);
        Http::assertNothingSent();

        $syncListener->handle($secondEvent);
        Http::assertSentCount(1);
        $this->assertSame([0.2, 0.5, 0.8], $product->fresh()->embedding);

        // Once maxWait (120s) has elapsed, withDelay() returns 0
        $thirdEvent = new CatalogEmbeddingSyncRequested('product', (int) $product->id);
        Cache::put(
            CatalogEmbeddingSyncRequested::FIRST_REQUESTED_PREFIX.$thirdEvent->debounceId(),
            now()->subSeconds(130)->getTimestamp(),
            300
        );
        $this->assertSame(0, $syncListener->withDelay($thirdEvent));
    }

    public function test_admin_activity_subscriber_records_three_role_actions_and_renders_audit_table_on_admin_users(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create([
            'name' => 'Giam Doc Audit',
            'email' => 'director.audit@bmw.com',
            'password' => bcrypt('12345678'),
            'role' => User::ROLE_ADMIN,
        ]);
        $sales = User::factory()->sales()->create([
            'name' => 'Sales Audit',
            'email' => 'sales.audit@bmw.com',
            'password' => bcrypt('12345678'),
        ]);
        $marketing = User::factory()->marketing()->create([
            'name' => 'Marketing Audit',
            'email' => 'marketing.audit@bmw.com',
            'password' => bcrypt('12345678'),
        ]);

        $category = Category::factory()->create(['name' => 'Sedan Audit', 'slug' => 'sedan-audit-p28']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 320i Sport Line',
            'slug' => 'bmw-320i-audit-p28',
            'type' => VehicleType::CAR,
            'price' => 1499000000,
            'deposit_amount' => 50000000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $serviceBooking = Appointment::factory()->for($car, 'product')->guest()->create([
            'guest_name' => 'Khach Audit Dich Vu',
            'guest_email' => 'audit.customer@example.com',
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Pending->value,
        ]);

        // 1. Login & Logout logged for internal showroom staff
        $this->post('/login', [
            'email' => $admin->email,
            'password' => '12345678',
        ])->assertRedirect();
        $this->post('/logout')->assertRedirect('/');

        $this->assertTrue(AdminActivityLog::query()->where('user_email', $admin->email)->where('action', 'auth.login')->exists());
        $this->assertTrue(AdminActivityLog::query()->where('user_email', $admin->email)->where('action', 'auth.logout')->exists());

        // 2. Sales updates service booking status -> logged
        Livewire::actingAs($sales)
            ->test(ServiceBookingTable::class)
            ->call('updateStatus', $serviceBooking->id, AppointmentStatus::Confirmed->value)
            ->assertHasNoErrors();

        $this->assertTrue(
            AdminActivityLog::query()
                ->where('user_email', $sales->email)
                ->where('user_role', User::ROLE_SALES)
                ->where('action', 'appointment.status_changed')
                ->exists()
        );

        // 3. Marketing dispatches promotion & updates product price -> both logged
        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Ưu đãi Audit Marketing',
                'content' => 'Tặng gói phủ Ceramic khi đặt lịch trong tháng.',
                'audience' => 'all',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $this->actingAs($marketing)
            ->put(route('admin.products.update', $car), [
                'name' => $car->name,
                'category_id' => $category->id,
                'type' => 'car',
                'price' => 1599000000,
                'deposit_amount' => 50000000,
                'stock' => 5,
                'specifications' => [
                    ['key' => 'Động cơ', 'value' => '2.0L TwinPower Turbo'],
                ],
                'is_active' => true,
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertTrue(
            AdminActivityLog::query()
                ->where('user_email', $marketing->email)
                ->where('user_role', User::ROLE_MARKETING)
                ->where('action', 'promotion.dispatched')
                ->exists()
        );
        $this->assertTrue(
            AdminActivityLog::query()
                ->where('user_email', $marketing->email)
                ->where('user_role', User::ROLE_MARKETING)
                ->where('action', 'product.updated')
                ->exists()
        );

        // 4. Admin creates new staff & updates role -> both logged
        $this->actingAs($admin)
            ->post(route('admin.users.store'), [
                'name' => 'Nhan Su Moi Audit',
                'email' => 'newstaff.audit@bmw.com',
                'password' => '12345678',
                'role' => User::ROLE_SALES,
            ])
            ->assertRedirect();

        $newStaff = User::query()->where('email', 'newstaff.audit@bmw.com')->firstOrFail();

        $this->actingAs($admin)
            ->patch(route('admin.users.update-role', $newStaff), [
                'role' => User::ROLE_MARKETING,
            ])
            ->assertRedirect();

        $this->assertTrue(AdminActivityLog::query()->where('action', 'staff.created')->exists());
        $this->assertTrue(AdminActivityLog::query()->where('action', 'staff.role_updated')->exists());

        // 5. Audit table renders cleanly on /admin/users for Admin (with whitespace-nowrap)
        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Nhật Ký Hoạt Động Nhân Sự Gần Đây')
            ->assertSee('director.audit@bmw.com')
            ->assertSee('sales.audit@bmw.com')
            ->assertSee('marketing.audit@bmw.com')
            ->assertSee('whitespace-nowrap', false);
    }

    public function test_end_to_end_consultation_flow_ai_chat_booking_custom_select_signed_url_crm_and_followup(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create(['email' => 'director.consult@bmw.com', 'role' => User::ROLE_ADMIN]);
        $sales = User::factory()->sales()->create(['email' => 'sales.consult@bmw.com']);
        $marketing = User::factory()->marketing()->create(['email' => 'marketing.consult@bmw.com']);

        $category = Category::factory()->create(['id' => 42, 'name' => 'BMW 3 Series', 'slug' => 'bmw-3-series-e2e']);
        $car = Product::factory()->for($category)->create([
            'id' => 77,
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport-e2e',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        // 1. AI Chat consultation queries (normal + hot buying intent) persist session & return booking suggestion
        $visitorId = 'visitor-consult-e2e-77';
        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tư vấn cho tôi mẫu BMW 330i và chi phí lăn bánh',
            'visitor_id' => $visitorId,
        ])->assertOk()
            ->assertJsonStructure(['answer', 'suggestions', 'visitor_id']);

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tôi muốn mua ngay BMW 330i trong tuần này, đặt lịch tư vấn giúp tôi',
            'visitor_id' => $visitorId,
        ])->assertOk()
            ->assertJsonPath('visitor_id', $visitorId);

        // 2. BookingFormWizard preserves REAL DB product IDs (77, NOT re-indexed 0!) and normalizes consultation alias
        $this->get(route('appointments.create', ['type' => 'consultation']))
            ->assertOk()
            ->assertSee('Tư vấn trực tiếp');

        Livewire::test(BookingFormWizard::class)
            ->set('type', 'consultation')
            ->assertSet('type', AppointmentType::Consult->value)
            ->set('categoryType', 'oto')
            ->assertSee('&quot;value&quot;:&quot;77&quot;', false)
            ->assertDontSee('&quot;value&quot;:&quot;0&quot;,&quot;label&quot;:&quot;BMW 330i M Sport&quot;', false);

        // Catalog custom-select options also preserve real DB IDs (42)
        Livewire::test(CatalogBrowser::class)
            ->assertSee('&quot;value&quot;:&quot;42&quot;', false);

        // 3. Guest (unauthenticated) submits HOT LEAD consultation booking linked with AI visitor_id
        $this->post(route('appointments.store'), [
            'guest_name' => 'Phạm Minh Tư Vấn',
            'guest_phone' => '0912345678',
            'guest_email' => 'minh.consult@example.com',
            'product_id' => $car->id,
            'type' => 'consultation',
            'appointment_date' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i'),
            'ai_visitor_id' => $visitorId,
            'notes' => 'Tôi muốn mua ngay và đặt cọc BMW 330i trong tuần này.',
        ])->assertRedirect(route('appointments.success'));

        $consultAppointment = Appointment::query()
            ->where('guest_email', 'minh.consult@example.com')
            ->firstOrFail();

        $this->assertSame(AppointmentType::Consult, $consultAppointment->type);
        $this->assertSame(77, (int) $consultAppointment->product_id);
        $this->assertSame(Appointment::LEAD_PRIORITY_HOT, $consultAppointment->lead_priority);
        $this->assertNotNull($consultAppointment->ai_conversation_id);
        $this->assertNotEmpty($consultAppointment->ai_chat_summary);

        Mail::assertQueued(AppointmentConfirmationMail::class);
        Mail::assertSent(HotLeadAlertMail::class);

        // 4. Guest Signed URL: view -> confirm -> reschedule via new_date & new_time -> cancel on a product-less consultation
        $this->get($consultAppointment->temporaryManageUrl())
            ->assertOk()
            ->assertSee('Phạm Minh Tư Vấn')
            ->assertSee('id="new_date"', false)
            ->assertSee('id="new_time"', false);

        $this->post($consultAppointment->temporaryManageUpdateUrl(), [
            'action' => 'confirm',
        ])->assertRedirect();
        $this->assertSame(AppointmentStatus::Confirmed, $consultAppointment->fresh()->status);

        $newDateStr = now()->addDays(5)->format('Y-m-d');
        $this->post($consultAppointment->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $newDateStr,
            'new_time' => '15:30',
            'reason' => 'Đổi sang chiều cuối tuần để tiện trao đổi báo giá.',
        ])->assertRedirect();
        $this->assertSame($newDateStr.' 15:30', $consultAppointment->fresh()->appointment_date->format('Y-m-d H:i'));

        // Product-less consultation (no product_id) -> confirm cancel & also verify followup renders cleanly without price
        $this->post(route('appointments.store'), [
            'guest_name' => 'Nguyễn Tư Vấn Chung',
            'guest_phone' => '0987654321',
            'guest_email' => 'general.consult@example.com',
            'type' => 'consult',
            'appointment_date' => now()->addDays(3)->setTime(14, 0)->format('Y-m-d H:i'),
            'notes' => 'Cần tư vấn tổng quan các dòng BMW đang có ưu đãi.',
        ])->assertRedirect(route('appointments.success'));

        $generalConsult = Appointment::query()
            ->where('guest_email', 'general.consult@example.com')
            ->firstOrFail();

        $this->assertNull($generalConsult->product_id);

        // 5. CRM orchestration: Admin/Sales verify customers product_id filter preserves real ID (77), complete consultations & check Mail preview
        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('&quot;value&quot;:&quot;77&quot;', false);

        $this->actingAs($sales)->get(route('admin.ai-conversations.index'))->assertOk();
        $this->actingAs($marketing)->get(route('admin.ai-conversations.index'))->assertForbidden();
        $this->actingAs($sales)
            ->get(route('admin.mail-preview.appointment', $consultAppointment))
            ->assertOk()
            ->assertSee('BMW 330i M Sport');

        Livewire::actingAs($sales)
            ->test(AppointmentTable::class)
            ->call('updateStatus', $consultAppointment->id, AppointmentStatus::Completed->value)
            ->call('updateStatus', $generalConsult->id, AppointmentStatus::Completed->value)
            ->assertHasNoErrors();

        Mail::assertQueued(PostTestDriveFollowupMail::class, 2);
        Mail::assertQueued(PostTestDriveFollowupMail::class, function (PostTestDriveFollowupMail $mail): bool {
            if (! $mail->hasTo('general.consult@example.com')) {
                return false;
            }

            // Product-less consultation renders HTML & plain-text safely without installment table
            $html = $mail->render();
            $this->assertStringContainsString('Nguyễn Tư Vấn Chung', $html);
            $this->assertStringNotContainsString('BẢNG ƯỚC TÍNH TÀI CHÍNH', $html);
            $mail->assertSeeInText('Nguyễn Tư Vấn Chung');

            return true;
        });

        // System/null actor status transition still logs safely to admin_activity_logs
        $consultAppointment->transitionStatusWithAutomation(AppointmentStatus::Cancelled, null);
        $this->assertTrue(
            AdminActivityLog::query()
                ->where('action', 'appointment.status_changed')
                ->where('user_email', 'sales.consult@bmw.com')
                ->exists()
        );
    }
}
