<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Mail\Admin\AccessoryOrderAlertMail;
use App\Mail\Admin\AppointmentAlertMail;
use App\Mail\Admin\HotLeadAlertMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Models\AccessoryOrder;
use App\Models\AccountingDailyClosing;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Crm\DocumentCodeGenerator;
use App\Services\Crm\InteractionTimelineRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class Phase33UxRefinementSecureCodesAccountingAndProductEditorTest extends TestCase
{
    use RefreshDatabase;

    private function createTopSuperAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Nguyễn Quốc Thái - Super Admin',
            'email' => 'thai@bmw.com',
            'role' => User::ROLE_ADMIN,
            'is_super_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function createRegularAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Quản Lý Vận Hành',
            'email' => 'admin.ops@bmw.com',
            'role' => User::ROLE_ADMIN,
            'is_super_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function createSales(string $name = 'Trần Quang Minh', string $email = 'sales.minh@bmw.com'): User
    {
        return User::factory()->create([
            'name' => $name,
            'email' => $email,
            'role' => User::ROLE_SALES,
            'is_super_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function createMarketing(): User
    {
        return User::factory()->create([
            'name' => 'Chuyên Viên Marketing',
            'email' => 'marketing.team@bmw.com',
            'role' => User::ROLE_MARKETING,
            'is_super_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @return array{0: Category, 1: Product, 2: Product}
     */
    private function createCatalogFixtures(): array
    {
        $category = Category::factory()->create([
            'name' => 'BMW Luxury Sedan',
            'slug' => 'bmw-luxury-sedan-'.uniqid(),
        ]);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 735i Pure Excellence',
            'slug' => 'bmw-735i-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 4999000000,
            'deposit_amount' => 100000000,
            'stock' => 3,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Camera Hành Trình BMW Advanced Car Eye 3.0',
            'slug' => 'camera-ace-3-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 14500000,
            'deposit_amount' => 0,
            'stock' => 15,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    public function test_part1_guest_self_service_confirmation_wording_and_reschedule_invalidates_prior_confirmation(): void
    {
        Mail::fake();
        [, $car] = $this->createCatalogFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Nguyễn Hoàng Nam',
            'guest_phone' => '0901234741',
            'guest_email' => 'nam.nguyen@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(9, 30),
            'status' => AppointmentStatus::Pending,
        ]);

        $manageUrl = URL::signedRoute('appointments.guest.manage', ['appointment' => $appointment->id]);
        $updateUrl = URL::signedRoute('appointments.guest.update', ['appointment' => $appointment->id]);

        // 1. Guest confirms attendance -> preserves Pending status, records timestamp & locks change cards
        $this->post($updateUrl, ['action' => 'confirm'])->assertRedirect();
        $appointment->refresh();

        $this->assertSame(AppointmentStatus::Pending, $appointment->status);
        $this->assertNotEmpty($appointment->meta_data['guest_confirmed_at'] ?? null);

        $expectedAppointmentTime = $appointment->appointment_date->format('H:i d/m/Y');
        $confirmedAtLabel = Carbon::parse($appointment->meta_data['guest_confirmed_at'])
            ->timezone('Asia/Ho_Chi_Minh')
            ->format('H:i d/m/Y');

        $this->get($manageUrl)
            ->assertOk()
            ->assertSee("Đã ghi nhận xác nhận đến Showroom cho lịch hẹn {$expectedAppointmentTime} (xác nhận lúc {$confirmedAtLabel}).")
            ->assertSee('data-guest-confirmed-change-lock', false)
            ->assertSee($appointment->publicReferenceCode())
            ->assertDontSee($appointment->internalVoucherCode())
            ->assertDontSee('#BMW-');

        // Confirm-locked appointment blocks guest reschedule & cancel POST with 403
        $newDateStr = now('Asia/Ho_Chi_Minh')->addDays(4)->format('Y-m-d');
        $this->post($updateUrl, [
            'action' => 'reschedule',
            'new_date' => $newDateStr,
            'new_time' => '15:30',
        ])->assertForbidden();

        // 2. Unconfirmed appointment can reschedule -> creates v=2 link and sets reschedule_pending_review
        $rescheduleAppt = Appointment::create([
            'guest_name' => 'Nguyễn Hoàng Nam 2',
            'guest_phone' => '0901234742',
            'guest_email' => 'nam.nguyen2@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now('Asia/Ho_Chi_Minh')->addDays(2)->setTime(9, 30),
            'status' => AppointmentStatus::Pending,
        ]);

        $this->post($rescheduleAppt->temporaryManageUpdateUrl(), [
            'action' => 'reschedule',
            'new_date' => $newDateStr,
            'new_time' => '15:30',
            'reason' => 'Bận họp buổi sáng, xin dời sang chiều.',
        ])->assertRedirect();

        $rescheduleAppt->refresh();
        $this->assertNull($rescheduleAppt->meta_data['guest_confirmed_at'] ?? null);
        $this->assertTrue((bool) ($rescheduleAppt->meta_data['reschedule_pending_review'] ?? false));

        $newAppointmentLabel = $rescheduleAppt->appointment_date->format('H:i — d/m/Y');
        $v2ManageUrl = $rescheduleAppt->temporaryManageUrl();

        $this->get($v2ManageUrl)
            ->assertOk()
            ->assertSee("Yêu cầu đổi lịch sang {$newAppointmentLabel} đã được ghi nhận. Chuyên viên BMW Showroom sẽ kiểm tra và xác nhận lại lịch hẹn mới cho Quý khách.")
            ->assertSee('Xác nhận chắc chắn đến Showroom');

        // 3. After showroom advisor acknowledges the reschedule, guest can confirm for the new schedule on the latest v=2 link
        $ackMeta = InteractionTimelineRecorder::normalizeMetaArray($rescheduleAppt->meta_data);
        $ackMeta['reschedule_pending_review'] = false;
        $rescheduleAppt->forceFill(['meta_data' => $ackMeta])->saveQuietly();

        $this->post($rescheduleAppt->temporaryManageUpdateUrl(), ['action' => 'confirm'])->assertRedirect();
        $rescheduleAppt->refresh();
        $this->assertNotEmpty($rescheduleAppt->meta_data['guest_confirmed_at'] ?? null);
        $this->assertFalse((bool) ($rescheduleAppt->meta_data['reschedule_pending_review'] ?? false));
    }

    public function test_part2_secure_reference_codes_internal_accounting_codes_and_clean_email_subjects(): void
    {
        [, $car, $accessory] = $this->createCatalogFixtures();

        $testDrive = Appointment::create([
            'guest_name' => 'Lê Minh Tuấn',
            'guest_phone' => '+84 901 234 741',
            'guest_email' => 'tuan.le@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 30),
            'status' => AppointmentStatus::Confirmed,
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Lê Minh Tuấn',
            'guest_phone' => '0901234741',
            'guest_email' => 'tuan.le@customer.vn',
            'type' => AppointmentType::Maintenance,
            'appointment_date' => now()->addDays(3)->setTime(14, 30),
            'status' => AppointmentStatus::Pending,
            'meta_data' => ['service_package' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)'],
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Lê Minh Tuấn',
            'customer_phone' => '0901234741',
            'customer_email' => 'tuan.le@customer.vn',
            'customer_address' => '88 Nguyễn Huệ, Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_CONFIRMED,
        ]);

        $yymmdd = now('Asia/Ho_Chi_Minh')->format('ymd');

        $this->assertMatchesRegularExpression("/^BMW-{$yymmdd}-[2-9A-HJ-NP-Z]{4}$/", (string) $testDrive->customer_order_code);
        $this->assertMatchesRegularExpression("/^SRV-{$yymmdd}-[2-9A-HJ-NP-Z]{4}$/", (string) $serviceBooking->customer_order_code);
        $this->assertMatchesRegularExpression("/^ACC-{$yymmdd}-[2-9A-HJ-NP-Z]{4}$/", (string) $order->customer_order_code);

        $this->assertSame("CT-{$yymmdd}-001", $testDrive->daily_sequence_code);
        $this->assertSame("CT-{$yymmdd}-002", $serviceBooking->daily_sequence_code);
        $this->assertSame("CT-{$yymmdd}-001", $order->daily_sequence_code);

        $this->assertSame('KH-4741-01', $testDrive->customer_internal_code);
        $this->assertSame('KH-4741-02', $serviceBooking->customer_internal_code);
        $this->assertSame('KH-4741-01', $order->customer_internal_code);

        // Customer emails show public reference code and NEVER expose #ID, CT- or KH-
        $confirmMail = new AppointmentConfirmationMail($testDrive, AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED);
        $this->assertStringContainsString("Mã {$testDrive->customer_order_code}", $confirmMail->envelope()->subject);
        $this->assertStringNotContainsString("#{$testDrive->id}", $confirmMail->envelope()->subject);
        $confirmMail->assertSeeInHtml($testDrive->customer_order_code);
        $confirmMail->assertDontSeeInHtml($testDrive->daily_sequence_code);
        $confirmMail->assertDontSeeInHtml('#BMW-');

        $serviceMail = new AppointmentConfirmationMail($serviceBooking, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED);
        $this->assertStringContainsString('[BMW Dịch Vụ]', $serviceMail->envelope()->subject);
        $this->assertStringContainsString("Mã {$serviceBooking->customer_order_code}", $serviceMail->envelope()->subject);

        $orderConfirmMail = new AccessoryOrderConfirmationMail($order);
        $this->assertSame("[BMW Lifestyle] Đã tiếp nhận đơn phụ kiện — Mã {$order->customer_order_code}", $orderConfirmMail->envelope()->subject);
        $orderConfirmMail->assertSeeInHtml($order->customer_order_code);
        $orderConfirmMail->assertDontSeeInHtml('#ACC-');
        $orderConfirmMail->assertDontSeeInHtml($order->daily_sequence_code);

        $orderStatusMail = new AccessoryOrderStatusMail($order);
        $this->assertStringContainsString("Mã {$order->customer_order_code}", $orderStatusMail->envelope()->subject);
        $orderStatusMail->assertDontSeeInHtml('#ACC-');

        // Internal admin emails contain all 3 codes
        $adminAlert = new AppointmentAlertMail($testDrive);
        $adminAlert->assertSeeInHtml($testDrive->customer_order_code);
        $adminAlert->assertSeeInHtml($testDrive->daily_sequence_code);
        $adminAlert->assertSeeInHtml($testDrive->customer_internal_code);

        $hotLeadAlert = new HotLeadAlertMail($testDrive);
        $hotLeadAlert->assertSeeInHtml($testDrive->customer_order_code);
        $hotLeadAlert->assertSeeInHtml($testDrive->daily_sequence_code);
        $hotLeadAlert->assertSeeInHtml($testDrive->customer_internal_code);

        $orderAlert = new AccessoryOrderAlertMail($order);
        $orderAlert->assertSeeInHtml($order->customer_order_code);
        $orderAlert->assertSeeInHtml($order->daily_sequence_code);
        $orderAlert->assertSeeInHtml($order->customer_internal_code);

        // CleanAndSyncShowroomDataCommand upgrades legacy code formats to Phase 33
        $testDrive->forceFill([
            'customer_order_code' => '#0901234741_1',
            'daily_sequence_code' => '21/11/2026/01',
            'customer_internal_code' => null,
        ])->saveQuietly();

        Artisan::call('showroom:clean-and-sync-data');
        $testDrive->refresh();
        $this->assertTrue(DocumentCodeGenerator::isModernReferenceCode($testDrive->customer_order_code));
        $this->assertTrue(DocumentCodeGenerator::isModernDailyCode($testDrive->daily_sequence_code));
        $this->assertTrue(DocumentCodeGenerator::isModernCustomerInternalCode($testDrive->customer_internal_code));
    }

    public function test_part3_and_part4_vietnamese_date_time_picker_public_sharp_0px_and_article_fixes(): void
    {
        [, $car] = $this->createCatalogFixtures();

        $article = Article::factory()->published()->create([
            'title' => 'Ra mắt BMW M5 Touring Thế Hệ Mới',
            'slug' => 'ra-mat-bmw-m5-touring',
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
        ]);

        Cache::flush();

        // 1. Homepage "Đọc tiếp" is a real clickable <a> link with article URL
        $homeRes = $this->get(route('home'));
        $homeRes->assertOk()
            ->assertSee($article->title)
            ->assertSee('href="'.route('articles.show', $article).'"', false);

        // 2. ArticleHub (/tim-hieu-them) has no search input and uses sharp 0px (rounded-none)
        $articlesRes = $this->get(route('articles.index'));
        $articlesRes->assertOk()
            ->assertDontSee('id="article-hub-search"', false)
            ->assertSee('rounded-none', false)
            ->assertDontSee('rounded-2xl', false);

        // 3. Booking page renders Smart Split Popover Vietnamese Date & Time Picker with Digital Input & Dual Wheel without Flatpickr CDN
        $bookingRes = $this->get(route('appointments.create'));
        $bookingRes->assertOk()
            ->assertDontSee('cdn.jsdelivr.net/npm/flatpickr', false)
            ->assertSee('data-picker-trigger="datetime"', false)
            ->assertSee('data-picker-popover="split"', false)
            ->assertSee('data-time-digital-hour', false)
            ->assertSee('data-time-digital-minute', false)
            ->assertSee('data-wheel-hours', false)
            ->assertSee('data-wheel-minutes', false)
            ->assertSee('data-picker-apply', false)
            ->assertSee('rounded-none', false);
    }

    public function test_part5_accounting_module_strictly_restricted_to_top_super_admin_with_daily_packs_closing_and_csv_bom(): void
    {
        $topSuperAdmin = $this->createTopSuperAdmin();
        $regularAdmin = $this->createRegularAdmin();
        $sales = $this->createSales();
        $marketing = $this->createMarketing();

        [, $car, $accessory] = $this->createCatalogFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Phạm Quốc Bảo',
            'guest_phone' => '0918222333',
            'guest_email' => 'bao.pham@customer.vn',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(9, 30),
            'status' => AppointmentStatus::Confirmed,
            'assigned_to' => $sales->id,
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Đặng Thu Thảo',
            'customer_phone' => '0988777666',
            'customer_email' => 'thao.dang@customer.vn',
            'customer_address' => '100 Lê Lợi, Q1, TP.HCM',
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_COMPLETED,
            'assigned_to' => $sales->id,
        ]);

        $todayDate = now('Asia/Ho_Chi_Minh')->format('Y-m-d');

        // 1. Regular Admin, Sales, and Marketing MUST receive 403 Forbidden on all accounting routes
        foreach ([$regularAdmin, $sales, $marketing] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)->get(route('admin.accounting.index'))->assertForbidden();
            $this->actingAs($unauthorizedUser)->get(route('admin.accounting.day', ['date' => $todayDate]))->assertForbidden();
            $this->actingAs($unauthorizedUser)->post(route('admin.accounting.close-day', ['date' => $todayDate]))->assertForbidden();
            $this->actingAs($unauthorizedUser)->get(route('admin.accounting.export'))->assertForbidden();
        }

        // Sidebar visibility check: regular admin does not see "Kế toán & Sổ sách"
        $this->actingAs($regularAdmin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Kế toán &amp; Sổ sách', false);

        // 2. Top Super Admin (thai@bmw.com) sees menu and accesses accounting dashboard
        $indexRes = $this->actingAs($topSuperAdmin)->get(route('admin.accounting.index'));
        $indexRes->assertOk()
            ->assertSee('Kế toán &amp; Sổ sách', false)
            ->assertSee('Doanh thu phụ kiện thực thu')
            ->assertSee('29.000.000 ₫')
            ->assertSee('Gói dữ liệu từng ngày bán hàng (Daily Sales Packs)')
            ->assertSee('Mở gói sổ ngày');

        // 3. Open Daily Sales Pack & perform Daily Closing
        $dayRes = $this->actingAs($topSuperAdmin)->get(route('admin.accounting.day', ['date' => $todayDate]));
        $dayRes->assertOk()
            ->assertSee($appointment->internalVoucherCode())
            ->assertSee($appointment->publicReferenceCode())
            ->assertSee($order->publicReferenceCode())
            ->assertSee($order->internalCustomerCode())
            ->assertSee('Đang mở');

        $this->actingAs($topSuperAdmin)
            ->post(route('admin.accounting.close-day', ['date' => $todayDate]), [
                'notes' => 'Đã đối soát chứng từ cuối ngày.',
            ])
            ->assertRedirect(route('admin.accounting.day', ['date' => $todayDate]));

        $this->assertDatabaseHas('accounting_daily_closings', [
            'closed_by' => $topSuperAdmin->id,
            'orders_count' => 1,
            'appointments_count' => 1,
            'completed_revenue' => 29000000,
            'notes' => 'Đã đối soát chứng từ cuối ngày.',
        ]);

        // 4. Export CSV with UTF-8 BOM
        $csvRes = $this->actingAs($topSuperAdmin)->get(route('admin.accounting.export', ['date' => $todayDate]));
        $csvRes->assertOk();
        $csvContent = $csvRes->streamedContent();
        $this->assertTrue(str_starts_with($csvContent, "\xEF\xBB\xBF"), 'CSV export must start with UTF-8 BOM.');
        $this->assertStringContainsString($order->publicReferenceCode(), $csvContent);
        $this->assertStringContainsString($order->internalVoucherCode(), $csvContent);
        $this->assertStringContainsString('Đặng Thu Thảo', $csvContent);
    }

    public function test_part6_product_editor_currency_dots_spec_presets_and_media_library_primary_and_delete(): void
    {
        $admin = $this->createTopSuperAdmin();
        [$category, $car] = $this->createCatalogFixtures();

        $img1 = ProductImage::create([
            'product_id' => $car->id,
            'path' => '/images/demo-car-1.jpg',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $img2 = ProductImage::create([
            'product_id' => $car->id,
            'path' => '/images/demo-car-2.jpg',
            'is_primary' => false,
            'sort_order' => 1,
        ]);

        $img3 = ProductImage::create([
            'product_id' => $car->id,
            'path' => '/images/demo-car-3.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $car))
            ->assertOk()
            ->assertSee('data-currency-display="price"', false)
            ->assertSee('data-testid="apply-bmw-preset-specs"', false)
            ->assertSee('data-testid="product-image-dropzone"', false)
            ->assertSee('Đặt làm ảnh chính')
            ->assertSee('Xóa ảnh');

        // Submit formatted dot currency, change primary image to img2, and delete img3
        $this->actingAs($admin)
            ->put(route('admin.products.update', $car), [
                'category_id' => $category->id,
                'name' => 'BMW 735i Pure Excellence LCI',
                'slug' => $car->slug,
                'type' => VehicleType::CAR->value,
                'price' => '5.199.000.000',
                'deposit_amount' => '150.000.000',
                'stock' => 4,
                'is_active' => 1,
                'primary_image_id' => $img2->id,
                'delete_image_ids' => [$img3->id],
                'specs' => [
                    ['key' => 'engine', 'value' => '3.0L TwinPower Turbo I6'],
                    ['key' => 'power', 'value' => '286 mã lực'],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $car->refresh();
        $this->assertSame(5199000000, (int) $car->price);
        $this->assertSame(150000000, (int) $car->deposit_amount);
        $this->assertSame('3.0L TwinPower Turbo I6', $car->specs['engine'] ?? null);
        $this->assertSame('286 mã lực', $car->specs['power'] ?? null);

        $this->assertDatabaseMissing('product_images', ['id' => $img3->id]);
        $this->assertFalse((bool) $img1->fresh()?->is_primary);
        $this->assertTrue((bool) $img2->fresh()?->is_primary);
    }

    public function test_part7_reset_crm_data_command_purges_customer_transactions_and_preserves_staff_and_catalog(): void
    {
        $topSuperAdmin = $this->createTopSuperAdmin();
        $sales = $this->createSales();
        [$category, $car, $accessory] = $this->createCatalogFixtures();

        $customerUser = User::factory()->create([
            'name' => 'Khách Hàng Thường',
            'email' => 'khach.thuong@gmail.com',
            'role' => 'customer',
            'is_super_admin' => false,
        ]);

        Appointment::create([
            'user_id' => $customerUser->id,
            'guest_name' => 'Khách Hàng Thường',
            'guest_phone' => '0905111222',
            'guest_email' => 'khach.thuong@gmail.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Hàng Thường',
            'customer_phone' => '0905111222',
            'customer_email' => 'khach.thuong@gmail.com',
            'customer_address' => '123 Nguyễn Trãi',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $aiSession = AiChatSession::create([
            'visitor_id' => 'visitor-reset-test',
            'status' => AiChatSession::STATUS_INTERESTED,
            'message_count' => 1,
        ]);

        AiChatMessage::create([
            'ai_chat_session_id' => $aiSession->id,
            'role' => 'user',
            'content' => 'Tư vấn xe BMW 735i',
        ]);

        AccountingDailyClosing::create([
            'closing_date' => now()->toDateString(),
            'closed_by' => $topSuperAdmin->id,
            'closed_at' => now(),
            'orders_count' => 1,
        ]);

        $this->artisan('showroom:reset-crm-data --force')
            ->assertExitCode(0);

        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, AccessoryOrder::count());
        $this->assertSame(0, AiChatSession::count());
        $this->assertSame(0, AiChatMessage::count());
        $this->assertSame(0, AccountingDailyClosing::count());
        $this->assertDatabaseMissing('users', ['email' => 'khach.thuong@gmail.com']);

        // Preserved staff and catalog
        $this->assertDatabaseHas('users', ['email' => $topSuperAdmin->email, 'is_super_admin' => true]);
        $this->assertDatabaseHas('users', ['email' => $sales->email]);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
        $this->assertDatabaseHas('products', ['id' => $car->id]);
        $this->assertDatabaseHas('products', ['id' => $accessory->id]);
    }
}
