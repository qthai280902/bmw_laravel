<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Ai\Agents\ShowroomStructuredAssistant;
use App\Ai\Tools\CalculateFinancialEstimateTool;
use App\Ai\Tools\CheckAccessoryStockTool;
use App\Ai\Tools\ListServicePackagesTool;
use App\Console\Commands\PruneOrphanMediaCommand;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Events\PromotionCampaignDispatched;
use App\Listeners\SendPromotionCampaignEmails;
use App\Livewire\Admin\AppointmentTable;
use App\Mail\Admin\ShowroomDailySummaryMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Notifications\Admin\MailDeliveryFailureNotification;
use App\Services\Ai\ShowroomAiToolkit;
use App\Services\Ai\ShowroomAssistantService;
use App\Services\Ai\StructuredOutputParser;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Concurrency\ShowroomConcurrency;
use App\Services\Inventory\AccessoryStockService;
use App\Services\Mail\GmailQuotaGuard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\ObjectSchema;
use Laravel\Ai\Tools\Request as AiToolRequest;
use Livewire\Livewire;
use ReflectionProperty;
use Tests\TestCase;

class Phase29PerformanceLocksRateLimitingStorageAndAiTest extends TestCase
{
    use RefreshDatabase;

    public function test_accessory_atomic_stock_lock_prevents_overselling_handles_lock_timeout_and_restores_once_on_cancel(): void
    {
        Cache::flush();

        $category = Category::factory()->create([
            'name' => 'Phụ kiện M Performance',
            'slug' => 'phu-kien-m-performance-p29',
        ]);

        $accessory = Product::factory()->for($category)->create([
            'name' => 'Ốp gương Carbon BMW M Performance',
            'slug' => 'op-guong-carbon-p29',
            'type' => VehicleType::ACCESSORY,
            'price' => 12500000,
            'stock' => 1,
            'is_active' => true,
        ]);

        // 1. Order #1 claims the last remaining unit
        $firstResponse = $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Nguyễn Minh Hoàng',
            'customer_phone' => '0901234567',
            'customer_address' => '12 Lê Duẩn, Quận 1, TP.HCM',
            'customer_email' => 'hoang.p29@bmw.test',
            'quantity' => 1,
        ]);

        $firstResponse->assertRedirect(route('accessory-orders.create', $accessory->slug));
        $firstResponse->assertSessionHas('success');

        $accessory->refresh();
        $this->assertSame(0, $accessory->stock);
        $this->assertDatabaseCount('accessory_orders', 1);

        $order = AccessoryOrder::query()->firstOrFail();
        $this->assertTrue((bool) ($order->meta_data['stock_deducted'] ?? false));
        $this->assertSame(1, (int) ($order->meta_data['stock_deducted_quantity'] ?? 0));

        // 2. Concurrent Order #2 fails cleanly with HTTP 409 JSON and stock never goes negative
        $secondResponse = $this->postJson(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Trần Văn B',
            'customer_phone' => '0907654321',
            'customer_address' => '45 Pasteur, Quận 1, TP.HCM',
            'quantity' => 1,
        ]);

        $secondResponse->assertStatus(409);
        $this->assertSame(0, $accessory->fresh()->stock);
        $this->assertDatabaseCount('accessory_orders', 1);

        // 3. Simulate LockTimeoutException (409 JSON without partial DB row)
        $accessory->update(['stock' => 2]);

        $this->partialMock(AccessoryStockService::class, static function ($mock): void {
            $mock->shouldReceive('placeOrder')->once()->andThrow(new LockTimeoutException);
        });

        $timeoutResponse = $this->postJson(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Lê Bảo Long',
            'customer_phone' => '0911223344',
            'customer_address' => '99 Nguyễn Huệ, TP.HCM',
            'quantity' => 1,
        ]);

        $timeoutResponse->assertStatus(409);
        $this->app->forgetInstance(AccessoryStockService::class);
        $this->assertSame(2, $accessory->fresh()->stock);

        // Verify Cache::lock key acquires & releases cleanly
        $realLock = Cache::lock(app(AccessoryStockService::class)->lockKey($accessory->id), AccessoryStockService::LOCK_TTL_SECONDS);
        $this->assertTrue($realLock->get());
        $realLock->release();

        // Restore stock to 0 for order #1 before cancellation idempotency check
        $accessory->update(['stock' => 0]);

        $admin = User::factory()->create(['email' => 'director.conc.p29@bmw.com', 'role' => User::ROLE_ADMIN]);

        // 4. First cancellation restores stock exactly +1
        $order->transitionToStatus(AccessoryOrder::STATUS_CANCELLED, 'Khách đổi mẫu', true, $admin);
        $accessory->refresh();
        $order->refresh();

        $this->assertSame(1, $accessory->stock);
        $this->assertNotEmpty($order->meta_data['stock_restored_at'] ?? null);

        // 5. Re-cancelling the same order is idempotent (stock stays 1, NOT 2)
        $order->transitionToStatus(AccessoryOrder::STATUS_CANCELLED, 'Cập nhật ghi chú', true, $admin);
        $this->assertSame(1, $accessory->fresh()->stock);
    }

    public function test_showroom_cache_service_uses_flexible_cache_and_observers_auto_invalidate(): void
    {
        Cache::flush();

        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-cache-p29']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 530i LCI Cache',
            'slug' => 'bmw-530i-lci-cache',
            'type' => VehicleType::CAR,
            'is_featured' => true,
            'is_active' => true,
        ]);

        Article::factory()->published()->create([
            'title' => 'Bài viết Cache BMW Ban Đầu',
            'slug' => 'bai-viet-cache-ban-dau',
        ]);

        /** @var ShowroomCacheService $cacheService */
        $cacheService = app(ShowroomCacheService::class);

        $firstHome = $cacheService->homeData();
        $this->assertCount(1, $firstHome['featuredProducts']);
        $this->assertSame($car->id, $firstHome['featuredProducts']->first()->id);

        // Second warm hit should hit cache with 0 queries
        DB::enableQueryLog();
        DB::flushQueryLog();
        $cachedHome = $cacheService->homeData();
        $warmQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame(0, $warmQueries);
        $this->assertSame($car->id, $cachedHome['featuredProducts']->first()->id);

        // Creating a new featured Product bumps catalog version & invalidates home cache automatically via ProductObserver
        $newCar = Product::factory()->for($category)->create([
            'name' => 'BMW M4 Competition New',
            'slug' => 'bmw-m4-competition-new',
            'type' => VehicleType::CAR,
            'is_featured' => true,
            'is_active' => true,
        ]);

        $refreshedHome = $cacheService->homeData();
        $this->assertCount(2, $refreshedHome['featuredProducts']);
        $this->assertTrue($refreshedHome['featuredProducts']->contains('id', $newCar->id));

        // Category & Dashboard auto-invalidation
        $categories = $cacheService->catalogCategories(false);
        $this->assertCount(1, $categories);

        $newCategory = Category::factory()->create(['name' => 'BMW i Electric', 'slug' => 'bmw-i-electric-p29']);
        $this->assertCount(2, $cacheService->catalogCategories(false));
        $this->assertTrue($cacheService->catalogCategories(false)->contains('id', $newCategory->id));

        $loaderRuns = 0;
        $summaryOne = $cacheService->dashboardSummary(function () use (&$loaderRuns): array {
            $loaderRuns++;

            return ['total' => 10];
        });
        $summaryTwo = $cacheService->dashboardSummary(function () use (&$loaderRuns): array {
            $loaderRuns++;

            return ['total' => 99];
        });
        $this->assertSame(['total' => 10], $summaryOne);
        $this->assertSame(['total' => 10], $summaryTwo);
        $this->assertSame(1, $loaderRuns);

        // AppointmentObserver flushes dashboard cache when an Appointment is saved
        Appointment::factory()->create(['product_id' => $car->id]);
        $summaryThree = $cacheService->dashboardSummary(function () use (&$loaderRuns): array {
            $loaderRuns++;

            return ['total' => 11];
        });
        $this->assertSame(['total' => 11], $summaryThree);
        $this->assertSame(2, $loaderRuns);
    }

    public function test_showroom_concurrency_wrapper_and_hot_spots_work_safely(): void
    {
        Cache::flush();
        Mail::fake();

        config(['showroom.concurrency_driver' => 'process']);

        // Inside PHPUnit / DB transactions, wrapper forces sync safely
        $this->assertSame('sync', ShowroomConcurrency::resolveDriver());

        $results = ShowroomConcurrency::run([
            'alpha' => static fn (): int => 2 + 3,
            'beta' => static fn (): string => 'BMW',
        ]);

        $this->assertSame(['alpha' => 5, 'beta' => 'BMW'], $results);
        $this->assertSame('sync', ShowroomConcurrency::lastDriverUsed());

        $admin = User::factory()->create(['email' => 'director.conc.p29@bmw.com', 'role' => User::ROLE_ADMIN]);
        $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav-conc-p29']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW X3 M Sport',
            'slug' => 'bmw-x3-m-sport-p29',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        Appointment::factory()->for($car)->create([
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'type' => AppointmentType::TestDrive,
        ]);

        // Dashboard uses Cache::flexible + ShowroomConcurrency::run cleanly
        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk();

        // Daily summary command runs concurrent aggregates without throwing serialization errors
        config(['mail.showroom_admin' => 'director.p29@bmw.com']);

        $this->artisan('showroom:daily-summary')
            ->assertExitCode(0);

        Mail::assertSent(ShowroomDailySummaryMail::class, 1);
    }

    public function test_rate_limiting_gmail_quota_guard_preserves_transactional_mail_and_throttles_ai_and_booking(): void
    {
        Cache::flush();
        Mail::fake();

        // 1. Registered limiters & job middleware
        $this->assertNotNull(RateLimiter::limiter('gmail-campaign'));
        $this->assertNotNull(RateLimiter::limiter('ai-concierge-chat'));
        $this->assertNotNull(RateLimiter::limiter('public-booking-submit'));

        $campaignListener = new SendPromotionCampaignEmails;
        $this->assertInstanceOf(ShouldBeEncrypted::class, $campaignListener);
        $this->assertInstanceOf(ShouldBeUnique::class, $campaignListener);
        $this->assertInstanceOf(ShouldQueue::class, $campaignListener);

        $middleware = $campaignListener->middleware();
        $this->assertCount(1, $middleware);
        $this->assertInstanceOf(RateLimited::class, $middleware[0]);
        $limiterProp = new ReflectionProperty(RateLimited::class, 'limiterName');
        $this->assertSame('gmail-campaign', $limiterProp->getValue($middleware[0]));

        // 2. GmailQuotaGuard blocks Marketing campaign above soft limit, alerts ONLY Admin + Sales once/day
        config(['mail.daily_soft_limit' => 5]);

        $admin = User::factory()->create(['email' => 'admin.quota@bmw.com', 'role' => User::ROLE_ADMIN]);
        $sales = User::factory()->sales()->create(['email' => 'sales.quota@bmw.com']);
        $marketing = User::factory()->marketing()->create(['email' => 'marketing.quota@bmw.com']);

        /** @var GmailQuotaGuard $quotaGuard */
        $quotaGuard = app(GmailQuotaGuard::class);
        $quotaGuard->recordSentMail(5);

        $this->assertSame(5, $quotaGuard->todayCount());
        $this->assertFalse($quotaGuard->canSendMarketingEmail());

        $event = new PromotionCampaignDispatched(
            campaignId: 'cmp-p29-01',
            offerTitle: 'Ưu đãi tháng 11',
            offerContent: 'Hỗ trợ 100% lệ phí trước bạ',
            recipients: [
                ['email' => 'vip1@customer.test', 'name' => 'Khách VIP 1', 'vehicle_name' => 'BMW 735i'],
                ['email' => 'vip2@customer.test', 'name' => 'Khách VIP 2', 'vehicle_name' => 'BMW X5'],
            ],
        );

        $campaignListener->handle($event);
        // Second call same day should NOT duplicate CRM bell alert
        $campaignListener->handle($event);

        Mail::assertNothingQueued();
        Mail::assertNothingSent();

        $adminAlerts = DatabaseNotification::query()
            ->where('notifiable_id', $admin->id)
            ->where('type', MailDeliveryFailureNotification::class)
            ->count();
        $salesAlerts = DatabaseNotification::query()
            ->where('notifiable_id', $sales->id)
            ->where('type', MailDeliveryFailureNotification::class)
            ->count();
        $marketingAlerts = DatabaseNotification::query()
            ->where('notifiable_id', $marketing->id)
            ->where('type', MailDeliveryFailureNotification::class)
            ->count();

        $this->assertSame(1, $adminAlerts);
        $this->assertSame(1, $salesAlerts);
        $this->assertSame(0, $marketingAlerts);

        // 3. Transactional booking confirmation email is NEVER blocked by marketing quota limit
        $appointment = Appointment::factory()->create([
            'guest_email' => 'khach.hot@bmw.test',
        ]);

        Mail::to($appointment->guest_email)->send(new AppointmentConfirmationMail($appointment));
        Mail::assertQueued(AppointmentConfirmationMail::class, 1);

        // 4. AI Chat endpoint returns JSON HTTP 429 cleanly after 15 requests/min
        config([
            'showroom_ai.enabled' => true,
            'ai.providers.gemini.key' => 'testing-key',
            'showroom.ai_chat_rate_per_minute' => 15,
            'showroom.ai_chat_rate_per_hour' => 60,
        ]);

        $answers = array_fill(0, 16, 'Showroom luôn sẵn sàng hỗ trợ Quý khách.');
        ShowroomAssistant::fake($answers);

        $aiIp = '198.51.100.77';
        for ($i = 0; $i < 15; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $aiIp])
                ->postJson(route('ai.api.assistant.ask'), [
                    'message' => "Hỏi mẫu BMW #{$i}",
                    'visitor_id' => 'visitor-rate-p29',
                ])->assertOk();
        }

        $rateLimitedAi = $this->withServerVariables(['REMOTE_ADDR' => $aiIp])
            ->postJson(route('ai.api.assistant.ask'), [
                'message' => 'Hỏi vượt hạn mức',
                'visitor_id' => 'visitor-rate-p29',
            ]);

        $rateLimitedAi->assertStatus(429)
            ->assertJsonStructure(['message', 'retry_after']);

        // 5. Public booking submit endpoint throttles bursts cleanly
        config(['mail.booking_rate_limit_per_minute' => 5]);
        $bookingIp = '198.51.100.88';
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-rl-p29']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 320i Sport Line',
            'slug' => 'bmw-320i-rl-p29',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        for ($i = 0; $i < 8; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => $bookingIp])
                ->post(route('appointments.store'), [
                    'guest_name' => "Khách Booking {$i}",
                    'guest_phone' => '0909888777',
                    'guest_email' => "guest{$i}@bmw.test",
                    'type' => 'consult',
                    'product_id' => $car->id,
                    'appointment_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
                ]);
        }

        $throttledBooking = $this->withServerVariables(['REMOTE_ADDR' => $bookingIp])
            ->postJson(route('appointments.store'), [
                'guest_name' => 'Khách Spam',
                'guest_phone' => '0909888777',
                'type' => 'consult',
                'product_id' => $car->id,
                'appointment_date' => now()->addDays(3)->format('Y-m-d H:i:s'),
            ]);

        $throttledBooking->assertStatus(429);
    }

    public function test_local_media_cleanup_orphan_prune_command_schedule_and_docker_hygiene(): void
    {
        Storage::fake('public');

        $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav-media-p29']);
        $productA = Product::factory()->for($category)->create([
            'name' => 'BMW X7 M60i Media A',
            'slug' => 'bmw-x7-media-a',
            'is_active' => true,
        ]);
        $productB = Product::factory()->for($category)->create([
            'name' => 'BMW X7 M60i Media B',
            'slug' => 'bmw-x7-media-b',
            'is_active' => true,
        ]);

        $disk = Storage::disk('public');
        $disk->put('products/old-img.jpg', 'old');
        $disk->put('products/new-img.jpg', 'new');
        $disk->put('products/shared-img.jpg', 'shared');
        $disk->put('products/orphan-stale.jpg', 'stale');
        $disk->put('products/orphan-fresh.jpg', 'fresh');
        $disk->put('articles/cover-old.jpg', 'old-cover');
        $disk->put('articles/cover-new.jpg', 'new-cover');
        $disk->put('images/bmw-hero-static.png', 'static-asset');

        touch($disk->path('products/orphan-stale.jpg'), now()->subHours(48)->getTimestamp());
        touch($disk->path('products/orphan-fresh.jpg'), now()->subMinutes(10)->getTimestamp());

        // 1. Updating ProductImage deletes old unreferenced file
        $imgA = ProductImage::create([
            'product_id' => $productA->id,
            'path' => 'products/old-img.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $imgA->update(['path' => 'products/new-img.jpg']);
        $disk->assertMissing('products/old-img.jpg');
        $disk->assertExists('products/new-img.jpg');

        // 2. Deleting Product sharing an image with ProductB keeps shared file intact
        ProductImage::create([
            'product_id' => $productA->id,
            'path' => 'products/shared-img.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
        ProductImage::create([
            'product_id' => $productB->id,
            'path' => 'products/shared-img.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $productA->delete();
        $disk->assertExists('products/shared-img.jpg');
        $disk->assertMissing('products/new-img.jpg');

        // 3. Article cover update & external URL tolerance
        $article = Article::factory()->published()->create([
            'cover_image' => 'articles/cover-old.jpg',
        ]);
        $article->update(['cover_image' => 'articles/cover-new.jpg']);
        $disk->assertMissing('articles/cover-old.jpg');
        $disk->assertExists('articles/cover-new.jpg');

        // 4. Prune command dry-run / safe 24h window / force-recent
        $this->artisan('showroom:prune-orphan-media --dry-run')
            ->expectsOutputToContain('products/orphan-stale.jpg')
            ->assertExitCode(0);

        $disk->assertExists('products/orphan-stale.jpg');
        $disk->assertExists('products/orphan-fresh.jpg');

        $this->artisan('showroom:prune-orphan-media')
            ->expectsOutputToContain('Đã dọn 1 tệp ảnh mồ côi')
            ->assertExitCode(0);

        $disk->assertMissing('products/orphan-stale.jpg');
        $disk->assertExists('products/orphan-fresh.jpg');
        $disk->assertExists('images/bmw-hero-static.png');
        $disk->assertExists('products/shared-img.jpg');
        $disk->assertExists('articles/cover-new.jpg');

        $this->artisan('showroom:prune-orphan-media --force-recent')
            ->assertExitCode(0);

        $disk->assertMissing('products/orphan-fresh.jpg');
        $disk->assertExists('images/bmw-hero-static.png');

        // 5. Weekly Sunday 04:00 schedule + Isolatable check
        $this->assertTrue(is_subclass_of(PruneOrphanMediaCommand::class, Isolatable::class));

        $schedule = app(Schedule::class);
        $pruneEvents = collect($schedule->events())
            ->filter(fn ($event): bool => str_contains((string) $event->command, 'showroom:prune-orphan-media'));

        $this->assertTrue($pruneEvents->isNotEmpty());
        $this->assertSame('0 4 * * 0', $pruneEvents->first()->expression);
        $this->assertSame('Asia/Ho_Chi_Minh', $pruneEvents->first()->timezone);

        // 6. Docker log rotation & .env.example hygiene
        $dockerCompose = file_get_contents(base_path('docker-compose.yml'));
        $this->assertNotFalse($dockerCompose);
        $this->assertStringContainsString('driver: "json-file"', $dockerCompose);
        $this->assertStringContainsString('max-size: "10m"', $dockerCompose);
        $this->assertStringContainsString('max-file: "3"', $dockerCompose);

        $envExample = file_get_contents(base_path('.env.example'));
        $this->assertNotFalse($envExample);
        $this->assertStringContainsString('LOG_CHANNEL=daily', $envExample);
        $this->assertStringContainsString('LOG_DAILY_DAYS=14', $envExample);
        $this->assertStringContainsString('SHOWROOM_CONCURRENCY_DRIVER=', $envExample);
        $this->assertStringContainsString('MAIL_DAILY_SOFT_LIMIT=', $envExample);
    }

    public function test_ai_toolkit_structured_output_db_grounding_and_crm_badge_rendering_preserve_query_budget(): void
    {
        Cache::flush();

        $categoryCar = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-ai-p29']);
        $categoryAcc = Category::factory()->create(['name' => 'Phụ kiện Chính hãng', 'slug' => 'phu-kien-ai-p29']);

        $car = Product::factory()->for($categoryCar)->create([
            'name' => 'BMW 520i M Sport LCI',
            'slug' => 'bmw-520i-m-sport-p29',
            'type' => VehicleType::CAR,
            'price' => 2969000000,
            'is_active' => true,
        ]);

        $inactiveCar = Product::factory()->for($categoryCar)->create([
            'name' => 'BMW Hidden Prototype',
            'slug' => 'bmw-hidden-prototype',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        $accessory = Product::factory()->for($categoryAcc)->create([
            'name' => 'Thảm sàn BMW All-Weather',
            'slug' => 'tham-san-bmw-p29',
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
            'stock' => 3,
            'is_active' => true,
        ]);

        // 1. Toolkit DB calculations & Laravel AI Tool wrappers
        /** @var ShowroomAiToolkit $toolkit */
        $toolkit = app(ShowroomAiToolkit::class);

        $estimate = $toolkit->calculateFinancialEstimate($car->id, 20, 60);
        $this->assertNotNull($estimate);
        $this->assertSame($car->id, $estimate['product_id']);
        $this->assertSame(2969000000, $estimate['price']);
        $this->assertGreaterThan(0, $estimate['monthly_payment']);

        $stockInfo = $toolkit->checkAccessoryStock($accessory->id);
        $this->assertTrue($stockInfo['found']);
        $this->assertSame(3, $stockInfo['stock']);
        $this->assertTrue($stockInfo['in_stock']);

        $packages = $toolkit->listServicePackages();
        $this->assertCount(4, $packages);

        $financeToolOut = json_decode((string) app(CalculateFinancialEstimateTool::class)->handle(new AiToolRequest([
            'product_id' => $car->id,
            'down_payment_percent' => 30,
            'months' => 48,
        ])), true);
        $this->assertSame('ok', $financeToolOut['status'] ?? null);
        $this->assertSame(48, $financeToolOut['estimate']['term_months'] ?? null);

        $stockToolOut = json_decode((string) app(CheckAccessoryStockTool::class)->handle(new AiToolRequest([
            'product_id' => $accessory->id,
        ])), true);
        $this->assertTrue($stockToolOut['in_stock'] ?? false);

        $serviceToolOut = json_decode((string) app(ListServicePackagesTool::class)->handle(new AiToolRequest([])), true);
        $this->assertSame('ok', $serviceToolOut['status'] ?? null);
        $this->assertCount(4, $serviceToolOut['packages'] ?? []);

        $structuredAgent = new ShowroomStructuredAssistant;
        $structuredTools = collect($structuredAgent->tools());
        $structuredToolNames = $structuredTools
            ->map(static fn ($tool): string => $tool->name())
            ->values()
            ->all();
        $this->assertCount(7, $structuredToolNames);
        $this->assertContains('calculate_financial_estimate', $structuredToolNames);
        $this->assertContains('check_accessory_stock', $structuredToolNames);
        $this->assertContains('list_service_packages', $structuredToolNames);

        $schemaFactory = new JsonSchemaTypeFactory;
        foreach ($structuredTools as $tool) {
            $schemaArray = (new ObjectSchema($tool->schema($schemaFactory)))->toArray();
            $this->assertSame('object', $schemaArray['type'] ?? null);
        }

        // 2. StructuredOutputParser validates JSON Schema & filters out inactive/fake product IDs
        /** @var StructuredOutputParser $parser */
        $parser = app(StructuredOutputParser::class);
        $schemaConfig = $parser->geminiGenerationConfig();
        $this->assertSame('application/json', $schemaConfig['responseMimeType'] ?? null);

        $structuredJson = json_encode([
            'reply_markdown' => "Mời Quý khách tham khảo [{$car->name}](/catalog/{$car->slug}) hoặc đặt gói phủ Ceramic.",
            'intent' => 'service_booking',
            'lead_level' => 'hot',
            'recommended_product_ids' => [$car->id, $inactiveCar->id, 999999],
            'recommended_service_package' => 'Phủ Ceramic',
            'prefill_booking' => [
                'type' => 'maintenance',
                'product_id' => $car->id,
                'summary' => 'Đặt lịch phủ Ceramic 9H+ cho BMW 520i',
            ],
        ], JSON_UNESCAPED_UNICODE);

        $parsed = $parser->parse((string) $structuredJson, 'Mình muốn đặt lịch phủ Ceramic cho BMW 520i');
        $this->assertFalse($parsed->usedFallback);
        $this->assertSame('service_booking', $parsed->intent);
        $this->assertSame('hot', $parsed->leadLevel);
        $this->assertSame([$car->id], $parsed->recommendedProductIds);
        $this->assertSame('Phủ Ceramic & Hiệu chỉnh bề mặt sơn (BMW Paint Protection)', $parsed->recommendedServicePackage);
        $this->assertNotNull($parsed->prefillBooking);
        $this->assertStringContainsString('service_package=', $parsed->prefillBooking['url']);

        // Markdown fallback mode preserves reply & infers metadata cleanly
        $fallbackParsed = $parser->parse(
            "Mẫu [{$car->name}](/catalog/{$car->slug}) đang có xe giao ngay, Quý khách có thể đăng ký lái thử cuối tuần.",
            'Tôi muốn đăng ký lái thử xe này cuối tuần',
            ['current_product' => ['id' => $car->id, 'type' => 'car']]
        );
        $this->assertTrue($fallbackParsed->usedFallback);
        $this->assertSame('test_drive', $fallbackParsed->intent);
        $this->assertContains($car->id, $fallbackParsed->recommendedProductIds);

        // 3. End-to-end AI chat persists structured meta_data & renders badges in CRM Conversation + Inspector Drawer
        config([
            'showroom_ai.enabled' => true,
            'ai.providers.gemini.key' => 'testing-key',
        ]);

        $structuredPayload = [
            'reply_markdown' => "Mẫu [{$car->name}](/catalog/{$car->slug}) giá 2,969 tỷ đồng đang có ưu đãi lái thử.",
            'intent' => 'test_drive',
            'lead_level' => 'hot',
            'recommended_product_ids' => [$car->id],
            'recommended_service_package' => null,
            'prefill_booking' => [
                'type' => 'test_drive',
                'product_id' => $car->id,
                'service_package' => null,
                'summary' => 'Đặt lịch lái thử BMW 520i M Sport LCI',
            ],
        ];

        ShowroomStructuredAssistant::fake([
            $structuredPayload,
        ]);

        $directStructuredResponse = ShowroomStructuredAssistant::make()->prompt('Đặt lịch lái thử BMW 520i');
        $this->assertSame('test_drive', $directStructuredResponse['intent'] ?? null);
        $this->assertSame('hot', $directStructuredResponse['lead_level'] ?? null);

        ShowroomAssistant::fake([
            json_encode($structuredPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        $visitorId = 'visitor-structured-p29';
        $chatResponse = $this->postJson(route('ai.api.assistant.ask'), [
            'message' => 'Báo giá lăn bánh và đặt lịch lái thử BMW 520i gấp giúp mình',
            'visitor_id' => $visitorId,
        ]);

        $chatResponse->assertOk()
            ->assertJsonPath('structured.intent', 'test_drive')
            ->assertJsonPath('structured.lead_level', 'hot');

        $session = AiChatSession::query()->where('visitor_id', $visitorId)->firstOrFail();
        $this->assertSame('test_drive', $session->last_intent);

        $assistantMsg = AiChatMessage::query()
            ->where('ai_chat_session_id', $session->id)
            ->where('role', 'assistant')
            ->firstOrFail();

        $this->assertIsArray($assistantMsg->meta_data);
        $this->assertSame('test_drive', $assistantMsg->meta_data['intent'] ?? null);
        $this->assertSame('hot', $assistantMsg->meta_data['lead_level'] ?? null);
        $this->assertSame([$car->id], $assistantMsg->meta_data['recommended_product_ids'] ?? null);

        $admin = User::factory()->create(['email' => 'director.conc.p29@bmw.com', 'role' => User::ROLE_ADMIN]);

        // View on /admin/ai-conversations/{session} displays structured badges
        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.show', $session))
            ->assertOk()
            ->assertSee('INTENT: test_drive')
            ->assertSee('LEAD: HOT')
            ->assertSee("#{$car->id}");

        // Link an Appointment and verify badges show inside Quick Inspector Drawer without breaking <= 8 SQL budget
        $appointment = Appointment::factory()->for($car)->create([
            'guest_name' => 'Phạm Quốc Bảo',
            'guest_phone' => '0919888999',
            'type' => AppointmentType::TestDrive,
            'ai_visitor_id' => $visitorId,
        ]);

        // Cold cache query budget check across 3 CRM routes (<= 8 queries each)
        foreach ([
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
        ] as $crmRoute) {
            Cache::flush();
            DB::enableQueryLog();
            DB::flushQueryLog();

            $this->actingAs($admin)->get($crmRoute)->assertOk();

            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queryCount, "Exceeded <= 8 SQL query budget on {$crmRoute} (ran {$queryCount} queries)");
        }

        // Open Quick Inspector Drawer on AppointmentTable & see structured AI badges
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee('BÀN GIAO NGỮ CẢNH AI')
            ->assertSee('INTENT: test_drive')
            ->assertSee('LEAD: HOT');

        // Keep unused import clear check happy for ShowroomAssistantService availability
        $this->assertTrue(class_exists(ShowroomAssistantService::class));
    }
}
