<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\NotificationBell;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\NewAccessoryOrderNotification;
use App\Notifications\Admin\NewAppointmentLeadNotification;
use App\Services\Ai\CloudflareEmbeddingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase26RealtimeNotificationsMailAndPoolTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_notifications_and_livewire_notification_bell_realtime_workflow(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create(['email' => 'admin.phase26@bmw.com']);
        $customer = User::factory()->create(['email' => 'user.phase26@example.com']);

        $sedan = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $accessoryCat = Category::factory()->create(['name' => 'Phụ kiện', 'slug' => 'phu-kien']);

        $car = Product::factory()->for($sedan)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->for($accessoryCat)->create([
            'name' => 'Thảm sàn BMW M Performance',
            'slug' => 'tham-san-bmw-m-performance',
            'type' => VehicleType::ACCESSORY,
            'price' => 8500000,
            'is_active' => true,
        ]);

        $visitorId = 'visitor-phase26-bell-01';
        $chatSession = AiChatSession::create([
            'visitor_id' => $visitorId,
            'context_product_id' => $car->id,
            'context_product_name' => $car->name,
            'context_product_slug' => $car->slug,
            'first_seen_at' => now()->subMinutes(15),
            'last_seen_at' => now()->subMinutes(5),
            'message_count' => 2,
            'last_message_preview' => 'Cần lấy xe BMW 330i trong tuần này',
        ]);
        AiChatMessage::create([
            'ai_chat_session_id' => $chatSession->id,
            'role' => 'user',
            'content' => 'Tôi cần mua ngay BMW 330i M Sport trong tuần này, có thể đặt cọc luôn không?',
        ]);

        // 1. Guest submits a HOT booking -> triggers NewAppointmentLeadNotification
        $this->post(route('appointments.store'), [
            'guest_name' => 'Nguyen Hot Lead',
            'guest_phone' => '0909888111',
            'guest_email' => 'hotlead@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive->value,
            'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
            'ai_visitor_id' => $visitorId,
            'notes' => 'Mua ngay trong tuần, đặt cọc gấp.',
        ])->assertRedirect(route('appointments.success'));

        // 2. Guest submits an AccessoryOrder -> triggers NewAccessoryOrderNotification
        $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Tran Phu Kien',
            'customer_phone' => '0909888222',
            'customer_address' => '268 Đội Cấn, Hà Nội',
            'customer_email' => 'phukien26@example.com',
            'quantity' => 2,
        ])->assertRedirect(route('accessory-orders.create', $accessory->slug));

        $adminNotifications = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $admin->id)
            ->latest()
            ->get();

        $this->assertCount(2, $adminNotifications);

        /** @var DatabaseNotification $leadNotification */
        $leadNotification = $adminNotifications->firstWhere('type', NewAppointmentLeadNotification::class);
        /** @var DatabaseNotification $orderNotification */
        $orderNotification = $adminNotifications->firstWhere('type', NewAccessoryOrderNotification::class);

        $this->assertNotNull($leadNotification);
        $this->assertNotNull($orderNotification);
        $this->assertSame('appointment_lead', $leadNotification->data['kind'] ?? null);
        $this->assertSame('hot', $leadNotification->data['lead_priority'] ?? null);
        $this->assertTrue((bool) ($leadNotification->data['has_ai_chat'] ?? false));
        $this->assertNotEmpty($leadNotification->data['ai_chat_summary'] ?? null);
        $this->assertSame('accessory_order', $orderNotification->data['kind'] ?? null);
        $this->assertSame(17000000, $orderNotification->data['total_amount'] ?? null);

        // Non-admin user must receive 403 on Livewire NotificationBell
        Livewire::actingAs($customer)
            ->test(NotificationBell::class)
            ->assertForbidden();

        // Admin interacts with Livewire NotificationBell filters & read actions
        Livewire::actingAs($admin)
            ->test(NotificationBell::class)
            ->assertViewHas('unreadCount', 2)
            ->assertViewHas('hasUnreadHotLead', true)
            ->assertSee('Nguyen Hot Lead')
            ->assertSee('Tran Phu Kien')
            ->call('setFilter', 'hot')
            ->assertSee('Nguyen Hot Lead')
            ->assertDontSee('Tran Phu Kien')
            ->call('setFilter', 'unread')
            ->assertSee('Nguyen Hot Lead')
            ->assertSee('Tran Phu Kien')
            ->call('markAsRead', (string) $leadNotification->id)
            ->assertViewHas('unreadCount', 1)
            ->assertViewHas('hasUnreadHotLead', false)
            ->call('markAllAsRead')
            ->assertViewHas('unreadCount', 0);

        $this->assertSame(0, $admin->unreadNotifications()->count());
    }

    public function test_appointment_confirmation_mail_attaches_dynamic_brochure_hot_lead_alert_and_browser_preview(): void
    {
        Mail::fake();
        Cache::flush();

        $admin = User::factory()->create(['email' => 'sales.director@bmw.com']);
        $customer = User::factory()->create(['email' => 'normal.buyer@example.com']);

        $category = Category::factory()->create(['name' => 'Sedan Hạng Sang', 'slug' => 'sedan-hang-sang']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 530i M Sport LCI',
            'slug' => 'bmw-530i-m-sport-lci',
            'type' => VehicleType::CAR,
            'price' => 3289000000,
            'deposit_amount' => 100000000,
            'specifications' => [
                'Engine' => '2.0L TwinPower Turbo',
                'Horsepower' => '258 HP',
            ],
            'is_active' => true,
        ]);

        $this->post(route('appointments.store'), [
            'guest_name' => 'Pham Gia Hung',
            'guest_phone' => '0911222333',
            'guest_email' => 'vip.hung@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Quote->value,
            'appointment_date' => now()->addDay()->format('Y-m-d H:i'),
            'notes' => 'Tôi cần chốt xe mua ngay và đặt cọc trong tuần này.',
        ])->assertRedirect(route('appointments.success'));

        $appointment = Appointment::query()->where('guest_email', 'vip.hung@example.com')->firstOrFail();
        $this->assertSame(Appointment::LEAD_PRIORITY_HOT, $appointment->lead_priority);

        Mail::assertQueued(AppointmentConfirmationMail::class, function (AppointmentConfirmationMail $mail) use ($car): bool {
            $brochureHtml = $mail->renderVehicleBrochureHtml($car);

            $mail->assertHasTo('vip.hung@example.com');
            $mail->assertSeeInHtml($car->name);
            $mail->assertSeeInHtml("BMW-{$car->slug}-Brochure.html");
            $mail->assertHasAttachedData($brochureHtml, "BMW-{$car->slug}-Brochure.html", ['mime' => 'text/html']);

            return str_contains($brochureHtml, 'Động cơ')
                && str_contains($brochureHtml, '2.0L TwinPower Turbo')
                && str_contains($brochureHtml, '3.289.000.000 VNĐ');
        });

        Mail::assertSent(HotLeadAlertMail::class, function (HotLeadAlertMail $mail) use ($admin, $appointment, $car): bool {
            $mail->assertHasTo($admin->email);
            $mail->assertHasTag('hot-lead');
            $mail->assertHasMetadata('appointment_id', (string) $appointment->id);
            $mail->assertSeeInHtml('HOT LEAD');
            $mail->assertSeeInHtml('Pham Gia Hung');
            $mail->assertSeeInHtml($car->name);

            return str_contains((string) $mail->envelope()->subject, '[HOT LEAD]');
        });

        // Browser Mailable Preview Route enforces Admin RBAC and renders rich HTML
        $this->actingAs($customer)
            ->get(route('admin.mail-preview.appointment', $appointment))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.mail-preview.appointment', $appointment))
            ->assertOk()
            ->assertSee($car->name)
            ->assertSee("BMW-{$car->slug}-Brochure.html");
    }

    public function test_accessory_order_status_mail_dispatches_on_livewire_status_update_and_skips_null_email(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'parts.admin@bmw.com']);
        $category = Category::factory()->create(['name' => 'Phụ kiện M Performance', 'slug' => 'phu-kien-m']);
        $accessory = Product::factory()->for($category)->create([
            'name' => 'Ốp gương Carbon M',
            'slug' => 'op-guong-carbon-m',
            'type' => VehicleType::ACCESSORY,
            'price' => 12500000,
            'is_active' => true,
        ]);

        $orderWithEmail = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Le Quoc Bao',
            'customer_phone' => '0909666777',
            'customer_address' => '800 Nguyễn Văn Linh, Quận 7',
            'customer_email' => 'parts.buyer@example.com',
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('updateStatus', $orderWithEmail->id, AccessoryOrder::STATUS_CONFIRMED)
            ->assertHasNoErrors();

        $orderWithEmail->refresh();
        $this->assertSame(AccessoryOrder::STATUS_CONFIRMED, $orderWithEmail->status);
        $this->assertNotNull($orderWithEmail->confirmed_at);

        Mail::assertSent(AccessoryOrderStatusMail::class, function (AccessoryOrderStatusMail $mail) use ($orderWithEmail): bool {
            $mail->assertHasTo('parts.buyer@example.com');
            $mail->assertSeeInHtml($orderWithEmail->publicReferenceCode());
            $mail->assertSeeInHtml('Đã xác nhận');
            $mail->assertSeeInHtml('25.000.000 VNĐ');

            return true;
        });

        // Order without customer_email transitions cleanly with no extra status email
        $orderNoEmail = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách không email',
            'customer_phone' => '0909555444',
            'customer_address' => 'Hà Nội',
            'customer_email' => null,
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->call('updateStatus', $orderNoEmail->id, AccessoryOrder::STATUS_CONFIRMED)
            ->call('updateStatus', $orderNoEmail->id, AccessoryOrder::STATUS_COMPLETED)
            ->assertHasNoErrors();

        Mail::assertSentTimes(AccessoryOrderStatusMail::class, 1);
    }

    public function test_http_pool_concurrent_batch_embeddings_with_failover_and_laravel_context_tracking(): void
    {
        Cache::flush();

        $cachePath = storage_path('framework/testing/phase26-pool-knowledge.json');
        File::delete($cachePath);

        try {
            config([
                'showroom_ai.enabled' => true,
                'showroom_ai.provider' => 'gemini',
                'showroom_ai.model' => null,
                'showroom_ai.gemini_keys.primary' => 'AIzaPhase26PoolKey01',
                'showroom_ai.gemini_keys.additional' => null,
                'showroom_ai.embeddings.enabled' => true,
                'showroom_ai.embeddings.accounts_list' => 'acc-pool-1:tok-pool-1,acc-pool-2:tok-pool-2',
                'showroom_ai.embeddings.numbered_accounts' => [],
                'showroom_ai.embeddings.account_id' => null,
                'showroom_ai.embeddings.api_token' => null,
                'showroom_ai.embeddings.knowledge_embeddings_path' => $cachePath,
            ]);

            $vector768 = array_fill(0, 768, 0.035);
            $attemptByAccount1 = 0;

            Http::fake([
                'https://api.cloudflare.com/client/v4/accounts/acc-pool-1/ai/run/*' => function (HttpRequest $request) use (&$attemptByAccount1, $vector768) {
                    $attemptByAccount1++;
                    $inputs = $request['input'] ?? [];

                    if ($attemptByAccount1 === 1) {
                        return Http::response(['success' => false], 429);
                    }

                    $data = array_map(fn (): array => $vector768, $inputs);

                    return Http::response([
                        'success' => true,
                        'result' => ['data' => $data],
                    ], 200);
                },
                'https://api.cloudflare.com/client/v4/accounts/acc-pool-2/ai/run/*' => function (HttpRequest $request) use ($vector768) {
                    $inputs = $request['input'] ?? [];
                    $data = array_map(fn (): array => $vector768, $inputs);

                    return Http::response([
                        'success' => true,
                        'result' => ['data' => $data],
                    ], 200);
                },
            ]);

            $embeddingService = app(CloudflareEmbeddingService::class);
            $batchVectors = $embeddingService->embedBatch([
                'vec_a' => 'title: BMW 330i | text: Sedan thể thao dẫn động cầu sau',
                'vec_b' => 'title: BMW X5 | text: SAV hạng sang 5+2 chỗ ngồi',
            ]);

            $this->assertArrayHasKey('vec_a', $batchVectors);
            $this->assertArrayHasKey('vec_b', $batchVectors);
            $this->assertCount(768, $batchVectors['vec_a']);
            $this->assertCount(768, $batchVectors['vec_b']);

            // Disable observer embedding during setup, then sync DB & FAQ concurrently via syncDatabaseEmbeddings & Artisan
            config(['showroom_ai.embeddings.enabled' => false]);
            $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav-phase26']);
            $product = Product::factory()->for($category)->create([
                'name' => 'BMW X5 M Competition',
                'slug' => 'bmw-x5-m-competition',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);
            $article = Article::factory()->create([
                'title' => 'Công nghệ xDrive thế hệ mới trên BMW SAV',
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now()->subHour(),
            ]);

            config(['showroom_ai.embeddings.enabled' => true]);
            $this->artisan('ai:sync-embeddings', ['--force' => true])->assertSuccessful();

            $product->refresh();
            $article->refresh();
            $this->assertIsArray($product->embedding);
            $this->assertCount(768, $product->embedding);
            $this->assertIsArray($article->embedding);
            $this->assertCount(768, $article->embedding);

            // Context tracking during AI request
            Context::flush();
            ShowroomAssistant::fake([
                'Mẫu BMW X5 M Competition mang lại trải nghiệm SAV hiệu năng cao chuẩn M.',
            ]);

            $this->postJson(route('ai.showroom-assistant.ask'), [
                'message' => 'Tư vấn giúp tôi BMW X5 M Competition',
                'visitor_id' => 'ctx-visitor-26',
            ])->assertOk();

            $this->assertSame('ctx-visitor-26', Context::get('ai_visitor_id'));
            $this->assertIsInt(Context::get('gemini_key_slot'));
        } finally {
            File::delete($cachePath);
            Context::flush();
        }
    }

    public function test_admin_accessory_orders_page_with_notification_bell_stays_within_eight_sql_queries(): void
    {
        Cache::flush();

        $admin = User::factory()->create(['email' => 'admin.budget@bmw.com']);
        $category = Category::factory()->create(['name' => 'Phụ kiện Ô tô', 'slug' => 'phu-kien-budget']);
        $accessory = Product::factory()->for($category)->create([
            'name' => 'Sạc BMW Wallbox Plus',
            'slug' => 'sac-bmw-wallbox-plus',
            'type' => VehicleType::ACCESSORY,
            'price' => 25000000,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 5; $i++) {
            AccessoryOrder::create([
                'product_id' => $accessory->id,
                'customer_name' => "Budget Customer {$i}",
                'customer_phone' => "090977000{$i}",
                'customer_address' => "{$i} Lê Duẩn, Quận 1",
                'quantity' => $i,
                'status' => AccessoryOrder::STATUS_PENDING,
            ]);
        }

        $appointment = Appointment::factory()->guest()->create([
            'guest_name' => 'VIP Notification Budget',
            'guest_phone' => '0909666111',
            'notes' => 'Mua ngay trong tuần này',
        ]);

        // Cold cache check on GET /admin/accessory-orders (with embedded Livewire NotificationBell)
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)
            ->get(route('admin.accessory-orders.index'))
            ->assertOk()
            ->assertSee('VIP Notification Budget')
            ->assertSee('Budget Customer 5');

        $coldQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            8,
            count($coldQueries),
            'Expected <= 8 SQL queries on /admin/accessory-orders even when NotificationBell cache is cold.'
        );

        // Warm cache check proves NotificationBell executes 0 extra SQL queries
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($admin)
            ->get(route('admin.accessory-orders.index'))
            ->assertOk();

        $warmQueries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertLessThan(count($coldQueries), count($warmQueries));
        $this->assertLessThanOrEqual(6, count($warmQueries));
        $this->assertNotNull($appointment->id);
    }
}
