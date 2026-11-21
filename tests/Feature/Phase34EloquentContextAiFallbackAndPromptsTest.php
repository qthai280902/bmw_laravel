<?php

namespace Tests\Feature;

use App\Collections\AccessoryOrderCollection;
use App\Collections\AppointmentCollection;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
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
use App\Notifications\Admin\NewAppointmentLeadNotification;
use App\Services\Ai\GeminiAgentRunner;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\LazyLoadingViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Ai\Exceptions\RateLimitedException;
use Tests\TestCase;

class Phase34EloquentContextAiFallbackAndPromptsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Context::flush();

        parent::tearDown();
    }

    public function test_ai_defaults_and_gemini_fallback_model_rotation_with_aq_key(): void
    {
        Cache::flush();

        $this->assertSame('gemini-3.1-flash-lite', config('showroom_ai.model'));
        $this->assertSame('gemini-3.5-flash-lite', config('showroom_ai.fallback_model'));
        $this->assertSame(20, config('showroom_ai.timeout_seconds'));
        $this->assertSame(60, config('showroom_ai.gemini_key_cooldown_seconds'));

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => 'gemini-3.1-flash-lite',
            'showroom_ai.fallback_model' => 'gemini-3.5-flash-lite',
            'showroom_ai.timeout_seconds' => 20,
            'showroom_ai.gemini_keys.primary' => 'AQ.test-key-001',
            'showroom_ai.gemini_keys.additional' => null,
            'ai.providers.gemini.key' => null,
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent' => Http::response([
                'error' => ['code' => 429, 'message' => 'Resource exhausted'],
            ], 429),
            'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent' => Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'Xin chào từ BMW Concierge fallback.']]]],
                ],
            ], 200),
        ]);

        /** @var GeminiAgentRunner $runner */
        $runner = app(GeminiAgentRunner::class);

        $result = $runner->run(function (string $provider, ?string $model): string {
            $apiKey = (string) config("ai.providers.{$provider}.key");
            $response = Http::withHeaders(['x-goog-api-key' => $apiKey])
                ->timeout($runner_timeout = app(GeminiAgentRunner::class)->defaultTimeoutSeconds())
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent", [
                    'contents' => [['parts' => [['text' => 'Tư vấn BMW X5']]]],
                ]);

            $this->assertSame(20, $runner_timeout);

            if ($response->status() === 429) {
                throw RateLimitedException::forProvider($provider);
            }

            return (string) data_get($response->json(), 'candidates.0.content.parts.0.text');
        }, 'test_fallback');

        $this->assertTrue($result['ok']);
        $this->assertSame('gemini-3.5-flash-lite', $result['model']);
        $this->assertSame('Xin chào từ BMW Concierge fallback.', $result['result']);

        Http::assertSent(function (HttpClientRequest $request): bool {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.1-flash-lite:generateContent'
                && $request->hasHeader('x-goog-api-key', 'AQ.test-key-001');
        });
        Http::assertSent(function (HttpClientRequest $request): bool {
            return $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent'
                && $request->hasHeader('x-goog-api-key', 'AQ.test-key-001');
        });

        $steps = Context::get('ai_steps', []);
        $this->assertContains('test_fallback:attempt:gemini-3.1-flash-lite:key_0', $steps);
        $this->assertContains('test_fallback:fallback_model:gemini-3.5-flash-lite', $steps);
        $this->assertContains('test_fallback:attempt:gemini-3.5-flash-lite:key_0', $steps);
    }

    public function test_request_context_propagates_to_timeline_and_queued_jobs(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan-ctx']);
        $car = Product::factory()->create([
            'category_id' => $category->id,
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'Nguyễn Trace Context',
            'guest_phone' => '0908111222',
            'guest_email' => 'trace34@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        $signedUrl = $appointment->temporaryManageUrl();
        $traceId = 'trace-bmw-phase34-001';

        $response = $this->withHeaders([
            'X-Request-Trace-Id' => $traceId,
        ])->get($signedUrl);

        $response->assertOk()
            ->assertHeader('X-Trace-Id', $traceId);

        $this->assertSame($traceId, Context::get('trace_id'));
        $this->assertSame($appointment->publicReferenceCode(), Context::get('crm_reference'));
        $this->assertSame($appointment->customer_internal_code, Context::getHidden('crm_internal_customer_code'));
        $this->assertSame($appointment->daily_sequence_code, Context::getHidden('crm_voucher_code'));

        $timelineEvent = InteractionTimelineRecorder::makeEvent(
            actorType: InteractionTimelineRecorder::ACTOR_SYSTEM,
            actorLabel: 'Hệ thống',
            title: 'Kiểm tra Trace ID',
            detail: 'Kế thừa tự động từ Laravel Context.',
        );
        $this->assertSame($traceId, $timelineEvent['trace_id']);

        // Verify Laravel Context serializes into queued jobs on database queue
        config(['queue.default' => 'database']);
        Mail::to($appointment->guest_email)->queue(new AppointmentConfirmationMail($appointment));

        $jobRow = DB::table('jobs')->latest('id')->first();
        $this->assertNotNull($jobRow);
        $this->assertStringContainsString($traceId, (string) $jobRow->payload);
    }

    public function test_eloquent_orm_hardening_strictness_morph_map_of_many_with_default_touches_and_collections(): void
    {
        $this->assertTrue(Model::preventsLazyLoading());
        $this->assertTrue(Model::preventsSilentlyDiscardingAttributes());

        // Enforce morph map aliases
        $this->assertSame('appointment', (new Appointment)->getMorphClass());
        $this->assertSame('accessory_order', (new AccessoryOrder)->getMorphClass());
        $this->assertSame('user', (new User)->getMorphClass());
        $this->assertSame('product', (new Product)->getMorphClass());
        $this->assertSame('article', (new Article)->getMorphClass());
        $this->assertSame(User::class, Relation::getMorphedModel('user'));

        $admin = User::factory()->topSuperAdmin()->create(['email' => 'boss34@bmw.com']);
        $sales = User::factory()->sales()->create(['name' => 'Sales Phase 34', 'email' => 'sales34@bmw.com']);

        $this->assertTrue($admin->isTopSuperAdmin());

        $category = Category::factory()->create(['name' => 'BMW M', 'slug' => 'bmw-m-p34']);
        $car = Product::factory()->create([
            'category_id' => $category->id,
            'type' => VehicleType::CAR,
            'price' => 4200000000,
            'deposit_amount' => 100000000,
        ]);
        $outOfStockCar = Product::factory()->outOfStock()->create([
            'category_id' => $category->id,
            'type' => VehicleType::CAR,
        ]);
        $this->assertSame(0, $outOfStockCar->stock);

        // ofMany tie-breaker verification: product with NO is_primary=1 returns lowest id image
        $img1 = ProductImage::create(['product_id' => $car->id, 'path' => 'products/first.jpg', 'is_primary' => false]);
        ProductImage::create(['product_id' => $car->id, 'path' => 'products/second.jpg', 'is_primary' => false]);
        $this->assertSame($img1->id, $car->fresh('primaryImage')->primaryImage->id);

        // product with explicit is_primary=1 prioritizes it over lower id
        $car2 = Product::factory()->create(['category_id' => $category->id, 'type' => VehicleType::CAR]);
        ProductImage::create(['product_id' => $car2->id, 'path' => 'products/a.jpg', 'is_primary' => false]);
        $primaryImg = ProductImage::create(['product_id' => $car2->id, 'path' => 'products/b.jpg', 'is_primary' => true]);
        $this->assertSame($primaryImg->id, $car2->fresh('primaryImage')->primaryImage->id);

        // Factory states + $hidden + AsArrayObject + withDefault()
        $unassigned = Appointment::factory()->hotLead()->rescheduledByGuest()->create([
            'user_id' => null,
            'guest_name' => 'Khách Chưa Gán',
            'guest_phone' => '0901119999',
            'guest_email' => 'unassigned34@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'assigned_to' => null,
        ]);
        $unassigned->forceFill(['assigned_to' => null])->saveQuietly();
        $unassigned = $unassigned->fresh('assignedSales');

        $this->assertSame('Chưa phân công', $unassigned->assignedSales->name);
        $this->assertFalse($unassigned->assignedSales->exists);

        $serialized = $unassigned->toArray();
        $this->assertArrayHasKey('customer_order_code', $serialized);
        $this->assertArrayNotHasKey('customer_internal_code', $serialized);
        $this->assertArrayNotHasKey('daily_sequence_code', $serialized);
        $this->assertArrayNotHasKey('meta_data', $serialized);

        // AsArrayObject direct key mutation
        $unassigned->meta_data['vip_tier'] = 'M-Performance';
        $unassigned->save();
        $this->assertSame('M-Performance', $unassigned->fresh()->meta_data['vip_tier'] ?? null);

        // MassAssignmentException on non-fillable attributes
        $discardExceptionThrown = false;
        try {
            Appointment::create([
                'guest_name' => 'Strict Test',
                'guest_phone' => '0902223334',
                'product_id' => $car->id,
                'type' => AppointmentType::Consult,
                'appointment_date' => now()->addDay(),
                'status' => AppointmentStatus::Pending,
                'non_existent_column_34' => 'should_throw',
            ]);
        } catch (MassAssignmentException) {
            $discardExceptionThrown = true;
        }
        $this->assertTrue($discardExceptionThrown);

        // LazyLoadingViolationException when accessing unloaded relationship in a multi-row collection
        Appointment::factory()->create([
            'user_id' => null,
            'guest_name' => 'Second Row Lazy Check',
            'guest_phone' => '0903334445',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'status' => AppointmentStatus::Pending,
        ]);
        $retrieved = Appointment::query()->get()->first();
        $lazyExceptionThrown = false;
        try {
            $retrieved->product;
        } catch (LazyLoadingViolationException) {
            $lazyExceptionThrown = true;
        }
        $this->assertTrue($lazyExceptionThrown);

        // $touches on AiChatMessage automatically updates parent AiChatSession updated_at
        $baseTime = Carbon::parse('2026-11-22 10:00:00', 'Asia/Ho_Chi_Minh');
        Carbon::setTestNow($baseTime);
        $session = AiChatSession::create([
            'visitor_id' => 'touch-vis-34',
            'first_seen_at' => $baseTime,
            'last_seen_at' => $baseTime,
        ]);
        Carbon::setTestNow($baseTime->copy()->addMinutes(15));
        $msg = AiChatMessage::create([
            'ai_chat_session_id' => $session->id,
            'role' => 'assistant',
            'content' => 'Xin chào',
            'meta_data' => ['lead_level' => 'warm'],
        ]);
        $msg->meta_data['lead_level'] = 'hot';
        $msg->save();
        $this->assertSame(
            $baseTime->copy()->addMinutes(15)->format('Y-m-d H:i:s'),
            $session->fresh()->updated_at->timezone('Asia/Ho_Chi_Minh')->format('Y-m-d H:i:s')
        );

        // Notification morph map storage uses 'user' + backfill migration works cleanly
        Notification::send($admin, new NewAppointmentLeadNotification($unassigned));
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $admin->id,
            'type' => NewAppointmentLeadNotification::class,
        ]);

        // Backfill legacy App\Models\User morph types safely
        DB::table('notifications')->where('notifiable_id', $admin->id)->update(['notifiable_type' => User::class]);
        $migration = require database_path('migrations/2026_11_22_000001_backfill_notifications_morph_types.php');
        $migration->up();
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => 'user',
            'notifiable_id' => $admin->id,
        ]);

        // Custom Eloquent Collections + Factory states
        $assignedAppt = Appointment::factory()->assignedTo($sales)->create([
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'status' => AppointmentStatus::Confirmed,
        ]);
        $orderDone = AccessoryOrder::factory()->completed()->assignedTo($sales)->create([
            'product_id' => Product::factory()->create([
                'category_id' => $category->id,
                'type' => VehicleType::ACCESSORY,
                'price' => 4500000,
                'deposit_amount' => 0,
            ])->id,
            'quantity' => 2,
        ]);
        $orderCancelled = AccessoryOrder::factory()->cancelled()->assignedTo($sales)->create([
            'product_id' => $orderDone->product_id,
            'quantity' => 1,
        ]);

        $unassignedOrder = AccessoryOrder::factory()->create(['assigned_to' => null]);
        $unassignedOrder->forceFill(['assigned_to' => null])->saveQuietly();
        $this->assertSame('Chưa phân công', $unassignedOrder->fresh('assignedSales')->assignedSales->name);
        $this->assertArrayNotHasKey('customer_internal_code', $orderDone->toArray());

        $appts = Appointment::query()->with('product:id,price,deposit_amount')->get();
        $orders = AccessoryOrder::query()->with('product:id,price')->get();

        $this->assertInstanceOf(AppointmentCollection::class, $appts);
        $this->assertInstanceOf(AccessoryOrderCollection::class, $orders);
        $this->assertSame(100000000, $appts->committedDepositValue());
        $this->assertSame(9000000, $orders->completedRevenue());
        $this->assertSame(4500000, $orders->cancelledValue());

        // Cold-cache <= 8 SQL query budget across all 3 CRM screens + Accounting + Sales Performance
        foreach ([
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
        ] as $crmRoute) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get($crmRoute)->assertOk();
            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $queryCount, "Cold-cache SQL budget exceeded on {$crmRoute}: {$queryCount}");
        }

        $this->actingAs($admin)->get(route('admin.accounting.index'))->assertOk()->assertSee('9.000.000');
        $this->actingAs($sales)->get(route('admin.users.performance', $sales))->assertOk()->assertSee('9.000.000');
    }

    public function test_artisan_prompts_commands_cache_clear_and_about_command(): void
    {
        $this->artisan('about')
            ->expectsOutputToContain('BMW Showroom')
            ->expectsOutputToContain('gemini-3.1-flash-lite')
            ->expectsOutputToContain('gemini-3.5-flash-lite')
            ->expectsOutputToContain('20s')
            ->assertExitCode(0);

        /** @var ShowroomCacheService $cacheService */
        $cacheService = app(ShowroomCacheService::class);
        $cacheService->salesRoster();
        $this->assertTrue(Cache::has(ShowroomCacheService::KEY_SALES_ROSTER));

        $this->artisan('showroom:cache-clear')->assertExitCode(0);
        $this->assertFalse(Cache::has(ShowroomCacheService::KEY_SALES_ROSTER));

        // Create staff via interactive Laravel Prompts form()
        $this->artisan('showroom:create-staff')
            ->expectsQuestion('Họ và tên nhân sự BMW', 'Trần Minh Khôi')
            ->expectsQuestion('Email nội bộ (@bmw.com)', 'sales.khoi34@bmw.com')
            ->expectsQuestion('Chọn phân quyền vai trò', User::ROLE_SALES)
            ->expectsQuestion('Mật khẩu khởi tạo (tối thiểu 8 ký tự)', 'Secret1234!')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'sales.khoi34@bmw.com',
            'role' => User::ROLE_SALES,
            'is_super_admin' => false,
        ]);

        // Create Super Admin via CLI flags
        $this->artisan('showroom:create-staff', [
            '--name' => 'Quản Trị Cấp Cao',
            '--email' => 'admin.p34@bmw.com',
            '--role' => User::ROLE_ADMIN,
            '--password' => 'SuperPass123!',
            '--super-admin' => true,
        ])->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'admin.p34@bmw.com',
            'role' => User::ROLE_ADMIN,
            'is_super_admin' => true,
        ]);

        // Reject invalid domain
        $this->artisan('showroom:create-staff', [
            '--name' => 'Ngoài Hệ Thống',
            '--email' => 'outsider@gmail.com',
            '--role' => User::ROLE_SALES,
            '--password' => 'Secret1234!',
        ])->assertExitCode(1);

        // Interactive reset cancelled via confirm()
        Appointment::factory()->guest()->create();
        $this->artisan('showroom:reset-crm-data')
            ->expectsConfirmation(
                'Bạn có chắc chắn muốn xóa dữ liệu giao dịch CRM khách hàng? (Nhân sự @bmw.com và kho sản phẩm được giữ nguyên 100%)',
                'no'
            )
            ->assertExitCode(1);
        $this->assertSame(1, Appointment::count());

        // Interactive reset confirmed + multiselect subset
        $this->artisan('showroom:reset-crm-data')
            ->expectsConfirmation(
                'Bạn có chắc chắn muốn xóa dữ liệu giao dịch CRM khách hàng? (Nhân sự @bmw.com và kho sản phẩm được giữ nguyên 100%)',
                'yes'
            )
            ->expectsQuestion('Chọn các phân hệ dữ liệu CRM cần làm sạch:', ['appointments'])
            ->assertExitCode(0);
        $this->assertSame(0, Appointment::count());

        // Non-interactive --force mode clears CRM and preserves @bmw.com staff
        Appointment::factory()->guest()->create();
        $this->assertSame(0, Artisan::call('showroom:reset-crm-data', ['--force' => true]));
        $this->assertSame(0, Appointment::count());
        $this->assertDatabaseHas('users', ['email' => 'sales.khoi34@bmw.com']);
    }
}
