<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Admin\LeadAssignedNotification;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Crm\DocumentCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class Phase32TypographyDualCodesSalesPerformanceAndHelpTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Quốc Thái',
            'email' => uniqid('admin_', true).'@bmw.com',
            'role' => User::ROLE_ADMIN,
            'email_verified_at' => now(),
        ]);
    }

    private function createSales(string $name, ?string $email = null): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email ?? uniqid('sales_', true).'@bmw.com',
            'role' => User::ROLE_SALES,
            'email_verified_at' => now(),
        ]);
    }

    private function createCatalogFixtures(): array
    {
        $category = Category::factory()->create([
            'name' => 'BMW Series Test',
            'slug' => 'bmw-series-'.uniqid(),
        ]);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW X5 xDrive40i',
            'slug' => 'bmw-x5-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 3899000000,
            'deposit_amount' => 100000000,
            'stock' => 2,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Bọc vô lăng M Sport',
            'slug' => 'boc-vo-lang-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 3500000,
            'deposit_amount' => 0,
            'stock' => 20,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    public function test_unified_typography_and_clean_guest_manage_page_render_correctly(): void
    {
        $sales = $this->createSales('Trần Quang Minh');
        [, $car] = $this->createCatalogFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Lê Minh Khang',
            'guest_phone' => '0909888777',
            'guest_email' => 'khang@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Confirmed,
            'assigned_to' => $sales->id,
        ]);

        // Layouts strip JetBrains Mono and monospace classes; Public uses sharp 0px (rounded-none)
        $appResponse = $this->get(route('products.index'));
        $appResponse->assertOk()
            ->assertSee('Be Vietnam Pro')
            ->assertDontSee('JetBrains Mono')
            ->assertSee('rounded-none');

        $guestUrl = URL::signedRoute('appointments.guest.manage', ['appointment' => $appointment->id]);
        $guestRes = $this->get($guestUrl);
        $guestRes->assertOk()
            ->assertSee('#F1F5F9', false)
            ->assertSee($appointment->customer_order_code)
            ->assertDontSee($appointment->daily_sequence_code)
            ->assertSee('Trần Quang Minh')
            ->assertDontSee('JetBrains Mono');
    }

    public function test_dual_code_generator_normalizes_phone_increments_integers_past_9_and_resets_daily_sequence(): void
    {
        $this->assertSame('0901234567', DocumentCodeGenerator::normalizePhoneDigits('+84 90-123-4567'));
        $this->assertSame('0987654321', DocumentCodeGenerator::normalizePhoneDigits('84987654321'));
        $this->assertSame('0000000000', DocumentCodeGenerator::normalizePhoneDigits(''));

        [, $car, $accessory] = $this->createCatalogFixtures();

        // Create 11 appointments for same normalized phone (+84901234567 & 0901234567) to verify KH-4567-09 -> -10 -> -11 & CT-YYMMDD-001 -> -011
        $created = [];
        for ($i = 1; $i <= 11; $i++) {
            $created[] = Appointment::create([
                'guest_name' => "Khách hàng {$i}",
                'guest_phone' => $i % 2 === 0 ? '+84 901 234 567' : '0901234567',
                'guest_email' => "khach{$i}@customer.vn",
                'product_id' => $car->id,
                'type' => AppointmentType::TestDrive,
                'appointment_date' => now()->addDays(2),
                'status' => AppointmentStatus::Pending,
            ]);
        }

        $todayYmd = now('Asia/Ho_Chi_Minh')->format('ymd');

        $this->assertMatchesRegularExpression("/^BMW-{$todayYmd}-[2-9A-HJ-NP-Z]{4}$/", (string) $created[0]->customer_order_code);
        $this->assertSame('KH-4567-01', $created[0]->customer_internal_code);
        $this->assertSame('KH-4567-09', $created[8]->customer_internal_code);
        $this->assertSame('KH-4567-10', $created[9]->customer_internal_code);
        $this->assertSame('KH-4567-11', $created[10]->customer_internal_code);

        $this->assertSame("CT-{$todayYmd}-001", $created[0]->daily_sequence_code);
        $this->assertSame("CT-{$todayYmd}-010", $created[9]->daily_sequence_code);
        $this->assertSame("CT-{$todayYmd}-011", $created[10]->daily_sequence_code);

        // AccessoryOrder sequence is independent of Appointments
        $order1 = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Nguyễn Lan Anh',
            'customer_phone' => '84901234567',
            'customer_email' => 'lananh@customer.vn',
            'customer_address' => '123 Lê Lợi, Q1',
            'quantity' => 1,
            'unit_price' => 3500000,
            'total_amount' => 3500000,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $this->assertMatchesRegularExpression("/^ACC-{$todayYmd}-[2-9A-HJ-NP-Z]{4}$/", (string) $order1->customer_order_code);
        $this->assertSame('KH-4567-01', $order1->customer_internal_code);
        $this->assertSame("CT-{$todayYmd}-001", $order1->daily_sequence_code);

        // Daily sequence resets on a different day in Asia/Ho_Chi_Minh
        $nextDayOrder = new AccessoryOrder([
            'product_id' => $accessory->id,
            'customer_name' => 'Ngày hôm sau',
            'customer_phone' => '0901234567',
            'customer_email' => 'next@customer.vn',
            'customer_address' => '45 Hai Bà Trưng',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);
        $nextDayOrder->created_at = now('Asia/Ho_Chi_Minh')->addDay()->setTimezone('UTC');
        $nextDayOrder->save();

        $tomorrowYmd = now('Asia/Ho_Chi_Minh')->addDay()->format('ymd');
        $this->assertMatchesRegularExpression("/^ACC-{$tomorrowYmd}-[2-9A-HJ-NP-Z]{4}$/", (string) $nextDayOrder->customer_order_code);
        $this->assertSame('KH-4567-02', $nextDayOrder->customer_internal_code);
        $this->assertSame("CT-{$tomorrowYmd}-001", $nextDayOrder->daily_sequence_code);
    }

    public function test_dual_codes_appear_in_all_html_and_plain_text_emails(): void
    {
        [, $car, $accessory] = $this->createCatalogFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Phạm Bảo Ngọc',
            'guest_phone' => '0912345678',
            'guest_email' => 'ngoc@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(1)->setTime(9, 0),
            'status' => AppointmentStatus::Confirmed,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'ai_lead_note' => 'Khách muốn lái thử cuối tuần.',
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Phạm Bảo Ngọc',
            'customer_phone' => '0912345678',
            'customer_email' => 'ngoc@customer.vn',
            'customer_address' => '88 Đồng Khởi, TP.HCM',
            'quantity' => 1,
            'unit_price' => 3500000,
            'total_amount' => 3500000,
            'status' => AccessoryOrder::STATUS_CONFIRMED,
        ]);

        // Customer appointment emails show public reference code and hide internal voucher code
        foreach ([
            new AppointmentConfirmationMail($appointment, 'received'),
            new GuestAppointmentChangeMail($appointment, 'rescheduled', now()->addDays(1)->setTime(9, 0), now()->addDays(2)->setTime(14, 0)),
            new AppointmentReminderMail($appointment),
            new PostTestDriveFollowupMail($appointment),
        ] as $customerMailable) {
            $customerMailable->assertSeeInHtml($appointment->customer_order_code);
            $customerMailable->assertDontSeeInHtml($appointment->daily_sequence_code);
            $customerMailable->assertSeeInText($appointment->customer_order_code);
            $customerMailable->assertDontSeeInText($appointment->daily_sequence_code);
        }

        // Internal admin appointment emails show all 3 codes
        foreach ([
            new HotLeadAlertMail($appointment),
            new AppointmentAlertMail($appointment),
        ] as $internalMailable) {
            $internalMailable->assertSeeInHtml($appointment->customer_order_code);
            $internalMailable->assertSeeInHtml($appointment->daily_sequence_code);
            $internalMailable->assertSeeInHtml($appointment->customer_internal_code);
        }

        // Customer accessory order emails show public reference code without #ACC-ID or internal voucher code
        foreach ([
            new AccessoryOrderConfirmationMail($order),
            new AccessoryOrderStatusMail($order),
        ] as $customerOrderMail) {
            $customerOrderMail->assertSeeInHtml($order->customer_order_code);
            $customerOrderMail->assertDontSeeInHtml("#ACC-{$order->id}");
            $customerOrderMail->assertDontSeeInHtml($order->daily_sequence_code);
        }

        // Internal admin accessory order email shows all 3 codes
        $adminOrderMail = new AccessoryOrderAlertMail($order);
        $adminOrderMail->assertSeeInHtml($order->customer_order_code);
        $adminOrderMail->assertSeeInHtml($order->daily_sequence_code);
        $adminOrderMail->assertSeeInHtml($order->customer_internal_code);
    }

    public function test_sales_auto_assignment_safe_null_fallback_manual_reassign_notification_and_cache_invalidation(): void
    {
        [, $car] = $this->createCatalogFixtures();

        // 1. When 0 sales users exist, assigned_to stays null safely without error
        $orphanAppointment = Appointment::create([
            'guest_name' => 'Chưa Có Sales',
            'guest_phone' => '0900111222',
            'guest_email' => 'nosales@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);
        $this->assertNull($orphanAppointment->fresh()->assigned_to);

        // 2. Admin creates Sales via UserController@store -> flushes salesRoster cache
        $admin = $this->createAdmin();
        $cacheService = app(ShowroomCacheService::class);
        $this->assertCount(0, $cacheService->salesRoster());

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Sales Một',
            'email' => 'sales1@bmw.com',
            'password' => 'password123',
            'role' => User::ROLE_SALES,
        ])->assertRedirect();

        $salesRosterAfterCreate = $cacheService->salesRoster();
        $this->assertCount(1, $salesRosterAfterCreate);
        $sales1 = $salesRosterAfterCreate->first();

        // Add second Sales via UserController@updateRole -> flushes salesRoster cache too
        $staff = User::factory()->create([
            'email' => 'marketing_to_sales@bmw.com',
            'role' => User::ROLE_MARKETING,
            'email_verified_at' => now(),
        ]);
        $this->actingAs($admin)->patch(route('admin.users.update-role', $staff), [
            'role' => User::ROLE_SALES,
        ])->assertRedirect();
        $this->assertCount(2, $cacheService->salesRoster());

        // 3. Batch distribute unassigned leads via CustomerController@distributeSales
        Notification::fake();
        $this->actingAs($admin)->post(route('admin.customers.distribute-sales'))->assertRedirect();
        $this->assertNotNull($orphanAppointment->fresh()->assigned_to);
        Notification::assertSentTo($orphanAppointment->fresh()->assignedSales, LeadAssignedNotification::class);

        // 4. Least-loaded auto-assignment balances between sales1 and staff
        $secondAppt = Appointment::create([
            'guest_name' => 'Cân Bằng Tải',
            'guest_phone' => '0900333444',
            'guest_email' => 'balance@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
        ]);
        $secondAppt->refresh();
        $this->assertSame((int) $staff->id, (int) $secondAppt->assigned_to);

        // 5. Admin reassigns manually via Livewire AppointmentTable, non-admin receives 403
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('assignToSales', $secondAppt->id, $sales1->id)
            ->assertHasNoErrors();
        $this->assertSame((int) $sales1->id, (int) $secondAppt->fresh()->assigned_to);

        Livewire::actingAs($sales1)
            ->test(AppointmentTable::class)
            ->call('assignToSales', $secondAppt->id, $staff->id)
            ->assertStatus(403);
    }

    public function test_sales_performance_page_authorization_svg_charts_and_filters(): void
    {
        $admin = $this->createAdmin();
        $salesA = $this->createSales('Nguyễn Hoàng Nam');
        $salesB = $this->createSales('Lê Bảo Trâm');
        [, $car, $accessory] = $this->createCatalogFixtures();

        $apptDone = Appointment::create([
            'guest_name' => 'Khách Hoàn Thành',
            'guest_phone' => '0933111222',
            'guest_email' => 'done@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDay(),
            'status' => AppointmentStatus::Completed,
            'assigned_to' => $salesA->id,
        ]);

        AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện',
            'customer_phone' => '0933111999',
            'customer_email' => 'acc@customer.vn',
            'customer_address' => '10 Võ Văn Tần',
            'quantity' => 2,
            'unit_price' => 3500000,
            'total_amount' => 7000000,
            'status' => AccessoryOrder::STATUS_COMPLETED,
            'assigned_to' => $salesA->id,
        ]);

        // Sales A can view self
        $selfRes = $this->actingAs($salesA)->get(route('admin.users.performance', $salesA));
        $selfRes->assertOk()
            ->assertSee('Nguyễn Hoàng Nam')
            ->assertSee('7.000.000')
            ->assertSee($apptDone->customer_order_code)
            ->assertSee('<svg', false)
            ->assertSee('Biểu đồ cột nhóm hiệu suất Sales')
            ->assertSee('Biểu đồ Donut tỷ trọng trạng thái');

        // Sales A cannot view Sales B -> 403
        $this->actingAs($salesA)->get(route('admin.users.performance', $salesB))->assertForbidden();

        // Admin can view Sales A and Sales B
        $this->actingAs($admin)->get(route('admin.users.performance', $salesB))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.performance', ['user' => $salesA->id, 'kind' => 'accessory', 'status' => 'completed']))
            ->assertOk()
            ->assertSee('Khách Phụ Kiện')
            ->assertViewHas('items', fn ($items) => $items->count() === 1 && $items->first()['customer_name'] === 'Khách Phụ Kiện');
    }

    public function test_help_popovers_upgraded_filters_cold_cache_sql_budget_and_cleanup_command(): void
    {
        $admin = $this->createAdmin();
        $sales = $this->createSales('Sales Kiểm Toán');
        [$category, $car, $accessory] = $this->createCatalogFixtures();

        // Out-of-stock product for stock_status filter
        $outOfStockCar = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW M8 Coupé Hết Hàng',
            'slug' => 'bmw-m8-het-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 7500000000,
            'stock' => 0,
            'is_active' => true,
        ]);

        $appointment = Appointment::create([
            'guest_name' => 'Võ Tấn Phát',
            'guest_phone' => '0988777666',
            'guest_email' => 'phat@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'assigned_to' => $sales->id,
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Trịnh Thu Thảo',
            'guest_phone' => '0977666555',
            'guest_email' => 'thao@customer.vn',
            'type' => AppointmentType::Maintenance,
            'service_package' => 'oil_change',
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
            'assigned_to' => $sales->id,
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Lý Gia Hân',
            'customer_phone' => '0966555444',
            'customer_email' => 'han@customer.vn',
            'customer_address' => '22 Pasteur, Q1',
            'quantity' => 1,
            'unit_price' => 3500000,
            'total_amount' => 3500000,
            'status' => AccessoryOrder::STATUS_PENDING,
            'assigned_to' => $sales->id,
        ]);

        $aiHotSession = AiChatSession::create([
            'visitor_id' => 'vis-hot-'.uniqid(),
            'display_name' => 'Khách Chat Hot',
            'status' => AiChatSession::STATUS_INTERESTED,
            'message_count' => 1,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
        AiChatMessage::create([
            'ai_chat_session_id' => $aiHotSession->id,
            'role' => 'assistant',
            'content' => 'Tôi đã ghi nhận lịch hẹn.',
            'response_reason' => 'ok',
            'meta_data' => ['lead_level' => 'hot', 'intent' => 'book_test_drive'],
        ]);

        // 1. Cold-cache <= 8 SQL queries check & (?) help-popover visibility on all 3 CRM screens
        foreach ([
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
        ] as $crmRoute) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $res = $this->actingAs($admin)->get($crmRoute);
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();

            $res->assertOk()
                ->assertSee('Hướng dẫn sử dụng trang');
            $this->assertLessThanOrEqual(8, $queries, "Cold cache exceeded 8 SQL queries ({$queries}) on {$crmRoute}");
        }

        // 2. Dual-code search & Sales filter in CRM Livewire components
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->set('search', $appointment->customer_order_code)
            ->assertSee($appointment->customer_order_code)
            ->assertDontSee('Lý Gia Hân')
            ->set('search', '')
            ->set('salesId', (string) $sales->id)
            ->assertSee($appointment->customer_order_code)
            ->call('inspectAppointment', $appointment->id)
            ->assertSee($appointment->daily_sequence_code)
            ->assertSee('Sales Kiểm Toán');

        Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->set('search', $serviceBooking->daily_sequence_code)
            ->assertSee($serviceBooking->customer_order_code);

        Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->set('search', $order->customer_order_code)
            ->assertSee($order->customer_order_code);

        // 3. Product stock & price filters
        $this->actingAs($admin)
            ->get(route('admin.products.index', ['stock_status' => 'out_of_stock', 'price_range' => 'over_5b']))
            ->assertOk()
            ->assertSee($outOfStockCar->name)
            ->assertViewHas('products', fn ($products) => $products->contains($outOfStockCar) && ! $products->contains($car));

        // 4. AI Conversation lead_level & converted filters
        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.index', ['lead_level' => 'hot', 'converted' => 'no']))
            ->assertOk()
            ->assertSee('Khách Chat Hot');

        // 5. Customer sales & priority filters
        $this->actingAs($admin)
            ->get(route('admin.customers.index', ['sales_id' => $sales->id, 'lead_priority' => 'hot']))
            ->assertOk()
            ->assertSee('Võ Tấn Phát');

        // 6. Cleanup & sync command works safely in both --dry-run and real mode
        Appointment::create([
            'guest_name' => 'Test Garbage Record',
            'guest_phone' => '0900000999',
            'guest_email' => 'garbage@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::Consult,
            'appointment_date' => now()->addDays(4),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->assertSame(0, Artisan::call('showroom:clean-and-sync-data', ['--dry-run' => true]));
        $this->assertDatabaseHas('appointments', ['guest_email' => 'garbage@example.com']);

        $this->assertSame(0, Artisan::call('showroom:clean-and-sync-data'));
        $this->assertDatabaseMissing('appointments', ['guest_email' => 'garbage@example.com']);
        $this->assertDatabaseHas('appointments', ['guest_email' => 'phat@customer.vn']);
    }
}
