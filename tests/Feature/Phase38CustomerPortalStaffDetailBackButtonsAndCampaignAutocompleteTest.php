<?php

namespace Tests\Feature;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Events\PromotionCampaignDispatched;
use App\Listeners\SendPromotionCampaignEmails;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\CustomerPromotionMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\CustomerEmailLog;
use App\Models\CustomerInstallmentPlan;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Crm\SalesAssignmentService;
use App\Services\Finance\InstallmentScheduleCalculator;
use App\Services\Mail\EmailTemplateRenderer;
use App\Support\AdminPageGuideRegistry;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase38CustomerPortalStaffDetailBackButtonsAndCampaignAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    private function createStaff(string $role = 'admin', bool $topSuperAdmin = true, ?string $name = null): User
    {
        $suffix = uniqid();
        $attrs = [
            'email' => "{$role}.{$suffix}@bmw.com",
            'is_active' => true,
        ];
        if ($name !== null) {
            $attrs['name'] = $name;
        }

        return match ($role) {
            'sales' => User::factory()->sales()->create($attrs),
            'marketing' => User::factory()->marketing()->create($attrs),
            default => $topSuperAdmin
                ? User::factory()->topSuperAdmin()->create($attrs)
                : User::factory()->create(array_merge($attrs, ['role' => User::ROLE_ADMIN, 'is_super_admin' => false])),
        };
    }

    private function createVehicleProduct(string $name = 'BMW X5 xDrive40i M Sport'): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW X Series '.uniqid(),
            'slug' => 'bmw-x-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'bmw-x5-'.uniqid(),
            'type' => 'car',
            'price' => 3899000000,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    private function createAccessoryProduct(string $name = 'BMW M Performance Carbon Key Case'): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW Phụ kiện '.uniqid(),
            'slug' => 'bmw-acc-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'bmw-acc-'.uniqid(),
            'type' => 'accessory',
            'price' => 4500000,
            'stock' => 20,
            'is_active' => true,
        ]);
    }

    public function test_item1_installment_calculator_public_portal_lookup_cross_link_and_seeder_cta(): void
    {
        Model::preventLazyLoading(true);

        $sales = $this->createStaff('sales', false, 'Trần Quốc Bảo');
        $vehicle = $this->createVehicleProduct('BMW 735i M Sport');
        $accessory = $this->createAccessoryProduct('BMW Floating Hub Caps');

        // Pre-seed legacy installment template with old /booking CTA to verify EmailTemplateSeeder upgrades existing DB rows
        EmailTemplate::query()->create([
            'code' => 'installment-due-reminder',
            'name' => 'Nhắc lịch thanh toán trả góp định kỳ',
            'category' => EmailTemplate::CATEGORY_INSTALLMENT,
            'subject' => 'Nhắc lịch {{vehicle_name}}',
            'heading' => 'Nhắc lịch thanh toán',
            'body_content' => 'Xin chào {{customer_name}}, tra cứu tại {{portal_lookup_url}}',
            'cta_label' => 'LIÊN HỆ CŨ',
            'cta_url' => url('/booking'),
            'accent_color' => '#1C69D4',
            'status' => EmailTemplate::STATUS_ACTIVE,
        ]);

        $this->seed(EmailTemplateSeeder::class);

        $dueTemplate = EmailTemplate::query()->where('code', 'installment-due-reminder')->firstOrFail();
        $rateTemplate = EmailTemplate::query()->where('code', 'installment-interest-adjustment')->firstOrFail();
        $this->assertSame('Tra cứu chi tiết lịch trả góp', $dueTemplate->cta_label);
        $this->assertSame('{{portal_lookup_url}}', $dueTemplate->cta_url);
        $this->assertSame('{{portal_lookup_url}}', $rateTemplate->cta_url);

        $renderer = app(EmailTemplateRenderer::class);
        $renderedMail = $renderer->renderTemplate($dueTemplate, $renderer->buildVariables(customerName: 'Lê Minh Khôi'));
        $this->assertStringContainsString('/tra-cuu', $renderedMail['cta_url']);
        $this->assertStringNotContainsString('{{', $renderedMail['cta_url']);

        // Create purchased appointment with phone + email + voucher + internal secret codes
        $purchasedAppt = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Lê Minh Khôi',
            'guest_email' => 'khoi.bmw38@example.com',
            'guest_phone' => '0908 123 456',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(10)->setTime(10, 0),
            'notes' => 'Khách đặt cọc BMW 735i',
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
                'care_voucher_code' => 'BMW-CARE-38VIP9',
                'admin_notes' => 'GHI_CHU_NOI_BO_TUYET_MAT_999',
            ],
        ]);

        // Service booking linked by phone
        Appointment::query()->create([
            'assigned_to' => $sales->id,
            'guest_name' => 'Lê Minh Khôi',
            'guest_phone' => '0908123456',
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(2)->setTime(9, 30),
            'meta_data' => [
                'service_package' => 'Bảo dưỡng định kỳ tiêu chuẩn BMW (Standard Maintenance)',
            ],
        ]);

        // Accessory order linked ONLY by email (proving Email <-> Phone cross-linking works!)
        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'assigned_to' => $sales->id,
            'customer_name' => 'Lê Minh Khôi',
            'customer_email' => 'khoi.bmw38@example.com',
            'customer_phone' => '0000000000',
            'customer_address' => '88 Đồng Khởi, Quận 1, TP.HCM',
            'quantity' => 2,
            'admin_notes' => 'GHI_CHU_DON_HANG_NOI_BO_888',
            'status' => AccessoryOrder::STATUS_COMPLETED,
        ]);

        // Installment plan: 1,200,000,000 VND over 12 months, 3 paid, 9.0%/yr reducing balance
        $plan = CustomerInstallmentPlan::query()->create([
            'customer_phone' => '0908123456',
            'customer_email' => null,
            'customer_name' => 'Lê Minh Khôi',
            'product_id' => $vehicle->id,
            'appointment_id' => $purchasedAppt->id,
            'bank_name' => 'Techcombank BMW Financial',
            'vehicle_price' => 4200000000,
            'down_payment' => 3000000000,
            'loan_amount' => 1200000000,
            'monthly_payment' => 109000000,
            'interest_rate' => 9.0,
            'interest_method' => CustomerInstallmentPlan::METHOD_REDUCING_BALANCE,
            'term_months' => 12,
            'paid_installments' => 3,
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
            'next_due_date' => now()->addDays(15)->format('Y-m-d'),
            'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
        ]);

        $calculator = app(InstallmentScheduleCalculator::class);
        $calc = $calculator->calculate($plan);

        $this->assertSame(1200000000, $calc['loan_amount']);
        $this->assertSame(300000000, $calc['paid_principal']);
        $this->assertSame(900000000, $calc['remaining_principal']);
        $this->assertSame(9, $calc['remaining_installments']);
        $this->assertCount(12, $calc['schedule']);
        $this->assertSame(1200000000, array_sum(array_column($calc['schedule'], 'principal_amount')));
        $this->assertSame(0, $calc['schedule'][11]['closing_balance']);
        $this->assertSame('paid', $calc['schedule'][0]['status']);
        $this->assertSame('paid', $calc['schedule'][2]['status']);
        $this->assertSame('current', $calc['schedule'][3]['status']);
        $this->assertSame('upcoming', $calc['schedule'][11]['status']);
        // Reducing balance interest decreases over time
        $this->assertGreaterThan($calc['schedule'][11]['interest_amount'], $calc['schedule'][0]['interest_amount']);

        // Flat method comparison: constant interest every month
        $flatPlan = $plan->replicate();
        $flatPlan->interest_method = CustomerInstallmentPlan::METHOD_FLAT;
        $flatCalc = $calculator->calculate($flatPlan);
        $this->assertSame($flatCalc['schedule'][0]['interest_amount'], $flatCalc['schedule'][11]['interest_amount']);

        // Lookup GET /tra-cuu
        $this->get(route('customer.lookup.index'))
            ->assertOk()
            ->assertSee('Cổng tra cứu hồ sơ');

        // Lookup POST /tra-cuu using +84 international phone format -> must cross-link accessory order & show strikethrough paid periods
        $lookupByPhone = $this->post(route('customer.lookup.search'), [
            'query' => '+84 908 123 456',
        ]);

        $lookupByPhone
            ->assertOk()
            ->assertSee('Lê Minh Khôi')
            ->assertSee('Trần Quốc Bảo')
            ->assertSee('Techcombank BMW Financial')
            ->assertSee('900.000.000 đ')
            ->assertSee('Đã thanh toán')
            ->assertSee('opacity-50 line-through', false)
            ->assertSee('BMW-CARE-38VIP9')
            ->assertSee('BMW Floating Hub Caps')
            ->assertDontSee('GHI_CHU_NOI_BO_TUYET_MAT_999')
            ->assertDontSee('GHI_CHU_DON_HANG_NOI_BO_888')
            ->assertDontSee((string) $purchasedAppt->daily_sequence_code)
            ->assertDontSee((string) $purchasedAppt->customer_internal_code);

        // Lookup POST /tra-cuu by Email -> must cross-link phone-only installment plan & service booking
        $this->post(route('customer.lookup.search'), [
            'query' => 'KHOI.BMW38@EXAMPLE.COM',
        ])
            ->assertOk()
            ->assertSee('Techcombank BMW Financial')
            ->assertSee('Bảo dưỡng định kỳ tiêu chuẩn BMW');

        // Unknown customer shows polite empty notice
        $this->post(route('customer.lookup.search'), [
            'query' => 'nobody.unknown38@example.com',
        ])
            ->assertOk()
            ->assertSeeHtml('data-lookup-empty-state');
    }

    public function test_item2_admin_subpages_render_unified_back_button_inside_page_header(): void
    {
        $topAdmin = $this->createStaff('admin', true);
        $vehicle = $this->createVehicleProduct();
        $accessory = $this->createAccessoryProduct();
        $category = Category::query()->firstOrFail();

        $article = Article::query()->create([
            'user_id' => $topAdmin->id,
            'title' => 'Bài viết kiểm tra Phase 38',
            'slug' => 'bai-viet-phase-38-'.uniqid(),
            'category' => Article::CATEGORY_SHOWROOM_NEWS,
            'excerpt' => 'Tóm tắt',
            'body' => 'Nội dung chi tiết',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        $this->seed(EmailTemplateSeeder::class);
        $template = EmailTemplate::query()->firstOrFail();

        $appt = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Kiểm Tra Back',
            'guest_email' => 'back38@example.com',
            'guest_phone' => '0912345678',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);

        $order = AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Phụ Kiện Back',
            'customer_email' => 'order38@example.com',
            'customer_phone' => '0912345679',
            'customer_address' => '12 Nguyễn Huệ, Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $aiSession = AiChatSession::query()->create([
            'visitor_id' => 'vis-back-38-'.uniqid(),
            'status' => 'open',
            'message_count' => 0,
            'last_seen_at' => now(),
        ]);

        $subpageUrls = [
            route('admin.email-templates.create'),
            route('admin.email-templates.edit', $template),
            route('admin.products.create'),
            route('admin.products.edit', $vehicle),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.articles.create'),
            route('admin.articles.edit', $article),
            route('admin.customers.show', ['phone' => '0912345678', 'email' => 'back38@example.com']),
            route('admin.users.create'),
            route('admin.users.show', $topAdmin),
            route('admin.users.edit', $topAdmin),
            route('admin.users.performance', $topAdmin),
            route('admin.accessory-orders.show', $order),
            route('admin.accounting.day', ['date' => now()->format('Y-m-d')]),
            route('admin.ai-conversations.show', $aiSession),
        ];

        foreach ($subpageUrls as $url) {
            $res = $this->actingAs($topAdmin)->get($url);
            $res->assertOk();
            $html = $res->getContent();
            $this->assertSame(
                1,
                substr_count((string) $html, 'data-admin-back-link'),
                "Expected exactly 1 unified data-admin-back-link on {$url}"
            );
        }
    }

    public function test_item3_staff_detail_top_super_admin_supremacy_active_lock_session_kick_and_lead_exclusion(): void
    {
        $topAdmin = $this->createStaff('admin', true, 'Top Super Admin');
        $regularAdmin = $this->createStaff('admin', false, 'Quản Lý Thường');
        $salesA = $this->createStaff('sales', false, 'Sales Alpha');
        $salesB = $this->createStaff('sales', false, 'Sales Beta');
        $marketing = $this->createStaff('marketing', false, 'Marketing One');

        // AdminPageGuideRegistry must register new staff pages
        $this->assertNotNull(AdminPageGuideRegistry::forRoute('admin.users.show'));
        $this->assertNotNull(AdminPageGuideRegistry::forRoute('admin.users.create'));
        $this->assertNotNull(AdminPageGuideRegistry::forRoute('admin.users.edit'));

        // Sales & Marketing can view own profile (200) and receive 403 for others
        $this->actingAs($salesA)->get(route('admin.users.show', $salesA))->assertOk()->assertSee('Sales Alpha');
        $this->actingAs($salesA)->get(route('admin.users.show', $salesB))->assertForbidden();
        $this->actingAs($marketing)->get(route('admin.users.show', $marketing))->assertOk()->assertSee('Marketing One');
        $this->actingAs($marketing)->get(route('admin.users.show', $salesA))->assertForbidden();

        // Regular Admin CAN view Top Super Admin profile read-only, but receives 403 on edit / update / updateRole / toggleActive
        $this->actingAs($regularAdmin)->get(route('admin.users.show', $topAdmin))->assertOk()->assertSee('Top Super Admin');
        $this->actingAs($regularAdmin)->get(route('admin.users.edit', $topAdmin))->assertForbidden();
        $this->actingAs($regularAdmin)->put(route('admin.users.update', $topAdmin), [
            'name' => 'Hacked Name',
            'email' => $topAdmin->email,
            'role' => User::ROLE_SALES,
        ])->assertForbidden();
        $this->actingAs($regularAdmin)->patch(route('admin.users.update-role', $topAdmin), [
            'role' => User::ROLE_SALES,
        ])->assertForbidden();
        $this->actingAs($regularAdmin)->patch(route('admin.users.toggle-active', $topAdmin))->assertForbidden();

        // Top Super Admin cannot self-lock
        $this->actingAs($topAdmin)->patch(route('admin.users.toggle-active', $topAdmin))->assertRedirect();
        $this->assertTrue($topAdmin->fresh()->isActiveAccount());

        // Top Super Admin can edit profile, password, job_title, department, phone of SalesA
        $this->actingAs($topAdmin)->put(route('admin.users.update', $salesA), [
            'name' => 'Sales Alpha Senior',
            'email' => $salesA->email,
            'phone' => '0988111222',
            'role' => User::ROLE_SALES,
            'job_title' => 'Senior Sales Consultant',
            'department' => 'BMW Flagship Luxury',
            'notes' => 'Phụ trách dòng 7 Series',
            'password' => 'NewSecret123!',
        ])->assertRedirect(route('admin.users.show', $salesA));

        $salesA->refresh();
        $this->assertSame('Sales Alpha Senior', $salesA->name);
        $this->assertSame('Senior Sales Consultant', $salesA->job_title);
        $this->assertTrue(Hash::check('NewSecret123!', $salesA->password));

        // Top Super Admin locks SalesA -> excluded from SalesAssignmentService & blocked at /login & kicked by AdminMiddleware
        $this->actingAs($topAdmin)->patch(route('admin.users.toggle-active', $salesA))->assertRedirect();
        $this->assertFalse($salesA->fresh()->isActiveAccount());

        $assignmentService = app(SalesAssignmentService::class);
        $picked = $assignmentService->pickLeastLoadedSales();
        $this->assertNotNull($picked);
        $this->assertSame($salesB->id, $picked->id);

        // Active session on locked account gets immediately logged out and redirected to /login by AdminMiddleware
        $this->actingAs($salesA->fresh())
            ->get(route('admin.appointments.index'))
            ->assertRedirect(route('login'));
        $this->assertGuest();

        // Login attempt with valid password on locked account is rejected
        $this->post(route('login'), [
            'email' => $salesA->email,
            'password' => 'NewSecret123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_item4_customer_360_email_logs_without_duplicates_and_installment_overview(): void
    {
        Model::preventLazyLoading(true);
        config()->set('mail.default', 'array');

        $this->seed(EmailTemplateSeeder::class);
        $admin = $this->createStaff('admin', true, 'Admin Chăm Sóc');
        $vehicle = $this->createVehicleProduct('BMW X7 xDrive40i');

        $appt = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $admin->id,
            'guest_name' => 'Phạm Quốc Hưng',
            'guest_email' => 'hung.x7@example.com',
            'guest_phone' => '0933444555',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);

        CustomerInstallmentPlan::query()->create([
            'customer_phone' => '0933444555',
            'customer_email' => 'hung.x7@example.com',
            'customer_name' => 'Phạm Quốc Hưng',
            'product_id' => $vehicle->id,
            'appointment_id' => $appt->id,
            'bank_name' => 'VIB BMW Auto',
            'loan_amount' => 2400000000,
            'monthly_payment' => 56000000,
            'interest_rate' => 8.4,
            'term_months' => 60,
            'paid_installments' => 12,
            'next_due_date' => '2027-01-10',
            'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
        ]);

        // 1. Transactional booking confirmation email via array mailer triggers MessageSent -> 1 booking_confirmation log
        Mail::to('hung.x7@example.com')->sendNow(new AppointmentConfirmationMail($appt));
        $this->assertSame(1, CustomerEmailLog::query()->where('mail_type', CustomerEmailLog::TYPE_BOOKING_CONFIRMATION)->count());

        // 2. Post-visit thank-you email via array mailer -> 1 post_visit_thank_you log
        Mail::to('hung.x7@example.com')->sendNow(new AppointmentCompletedThankYouMail($appt, Appointment::OUTCOME_PURCHASED, 'BMW-CARE-X7VIP'));
        $this->assertSame(1, CustomerEmailLog::query()->where('mail_type', CustomerEmailLog::TYPE_POST_VISIT_THANK_YOU)->count());

        // 3. Quick 1-to-1 templated email (sync queue -> triggers MessageSent too!) MUST NOT duplicate!
        $installmentTemplate = EmailTemplate::query()->where('code', 'installment-due-reminder')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.customers.send-email', ['phone' => '0933444555']), [
            'route_email' => 'hung.x7@example.com',
            'recipient_email' => 'hung.x7@example.com',
            'template_id' => $installmentTemplate->id,
        ])->assertRedirect();

        $this->assertSame(1, CustomerEmailLog::query()->where('mail_type', CustomerEmailLog::TYPE_INSTALLMENT_REMINDER)->count());

        // 4. Promotion campaign email via SendPromotionCampaignEmails (triggers MessageSent too!) MUST NOT duplicate!
        $listener = new SendPromotionCampaignEmails;
        $listener->handle(new PromotionCampaignDispatched(
            campaignId: 'phase38-dedup-campaign',
            offerTitle: 'Ưu đãi đặc quyền chủ xe {{vehicle_name}}',
            offerContent: 'Chào {{customer_name}}, kiểm tra hồ sơ tại {{portal_lookup_url}}.',
            recipients: [[
                'email' => 'hung.x7@example.com',
                'name' => 'Phạm Quốc Hưng',
                'vehicle_name' => 'BMW X7 xDrive40i',
                'phone' => '0933444555',
            ]],
            sentBy: $admin->id,
        ));

        $this->assertSame(1, CustomerEmailLog::query()->where('mail_type', CustomerEmailLog::TYPE_PROMOTION_CAMPAIGN)->count());
        $this->assertSame(4, CustomerEmailLog::query()->where('customer_email', 'hung.x7@example.com')->count());

        // 5. Render Customer 360° page -> shows overview debt, monthly schedule strikethrough, and all 4 email logs without lazy loading!
        $this->actingAs($admin)
            ->get(route('admin.customers.show', ['phone' => '0933444555', 'email' => 'hung.x7@example.com']))
            ->assertOk()
            ->assertSeeHtml('data-customer-installment-overview')
            ->assertSee('Đang trả góp')
            ->assertSee('1.920.000.000 đ')
            ->assertSee('Còn 48 / 60 kỳ')
            ->assertSee('opacity-50 line-through', false)
            ->assertSeeHtml('data-customer-email-logs-tab')
            ->assertSee('Lịch sử thư đã gửi (4)')
            ->assertSee('Admin Chăm Sóc');
    }

    public function test_item5_purchased_suggestions_strict_filter_and_selected_chips_campaign_dispatch(): void
    {
        Mail::fake();
        $marketing = $this->createStaff('marketing', false);
        $vehicle = $this->createVehicleProduct('BMW i7 xDrive60');
        $accessory = $this->createAccessoryProduct('BMW Wallbox Gen 4');

        // 4 ELIGIBLE PURCHASED CUSTOMERS:
        // (1) Purchased vehicle appointment
        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Mua Xe i7',
            'guest_email' => 'buyer.car@example.com',
            'guest_phone' => '0971112233',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(5)->setTime(10, 0),
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_PURCHASED],
        ]);

        // (2) Installment plan customer
        CustomerInstallmentPlan::query()->create([
            'customer_phone' => '0972223344',
            'customer_email' => 'buyer.installment@example.com',
            'customer_name' => 'Khách Trả Góp i7',
            'product_id' => $vehicle->id,
            'bank_name' => 'Vietcombank',
            'loan_amount' => 2000000000,
            'monthly_payment' => 45000000,
            'interest_rate' => 7.8,
            'term_months' => 60,
            'paid_installments' => 4,
            'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
        ]);

        // (3) Completed accessory order customer
        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'customer_name' => 'Khách Mua Phụ Kiện',
            'customer_email' => 'buyer.accessory@example.com',
            'customer_phone' => '0973334455',
            'customer_address' => '99 Lê Lợi, Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_COMPLETED,
        ]);

        // (4) Completed service booking customer
        Appointment::query()->create([
            'guest_name' => 'Khách Bảo Dưỡng Xong',
            'guest_email' => 'buyer.service@example.com',
            'guest_phone' => '0974445566',
            'type' => AppointmentType::Detailing->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(3)->setTime(14, 0),
        ]);

        // 3 INELIGIBLE NON-PURCHASED CUSTOMERS (MUST BE EXCLUDED!):
        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Mới Đặt Lịch',
            'guest_email' => 'notbuyer.pending@example.com',
            'guest_phone' => '0978880001',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);

        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Hủy Lịch',
            'guest_email' => 'notbuyer.cancelled@example.com',
            'guest_phone' => '0978880002',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Cancelled->value,
            'appointment_date' => now()->addDays(2)->setTime(11, 0),
        ]);

        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Xem Xe Không Mua',
            'guest_email' => 'notbuyer.visited@example.com',
            'guest_phone' => '0978880003',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(1)->setTime(15, 0),
            'meta_data' => ['completion_outcome' => Appointment::OUTCOME_VISITED_NO_PURCHASE],
        ]);

        // Search all with "097" -> must return ONLY the 4 eligible customers, zero non-buyers
        $allRes = $this->actingAs($marketing)
            ->getJson(route('admin.customers.purchased-suggestions', ['q' => '097']))
            ->assertOk();

        $emails = array_column($allRes->json(), 'email');
        $this->assertCount(4, $emails);
        $this->assertContains('buyer.car@example.com', $emails);
        $this->assertContains('buyer.installment@example.com', $emails);
        $this->assertContains('buyer.accessory@example.com', $emails);
        $this->assertContains('buyer.service@example.com', $emails);
        $this->assertNotContains('notbuyer.pending@example.com', $emails);
        $this->assertNotContains('notbuyer.cancelled@example.com', $emails);
        $this->assertNotContains('notbuyer.visited@example.com', $emails);

        // Search by email fragment & +84 phone format
        $this->actingAs($marketing)
            ->getJson(route('admin.customers.purchased-suggestions', ['q' => 'buyer.accessory']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.email', 'buyer.accessory@example.com');

        $this->actingAs($marketing)
            ->getJson(route('admin.customers.purchased-suggestions', ['q' => '+84 971 112']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.email', 'buyer.car@example.com');

        // Dispatch campaign with selected_recipient_emails[] (2 valid buyers + 1 injected non-buyer) -> server filters out non-buyer!
        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Tri ân đặc quyền {{customer_name}}',
                'content' => 'Ưu đãi riêng cho {{vehicle_name}}, xem hồ sơ tại {{portal_lookup_url}}.',
                'audience' => 'all',
                'selected_recipient_emails' => [
                    'buyer.car@example.com',
                    'buyer.accessory@example.com',
                    'notbuyer.visited@example.com',
                ],
            ])
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'Đã phát chiến dịch ưu đãi tới 2 khách hàng mục tiêu.');

        Mail::assertSent(CustomerPromotionMail::class, 2);
        Mail::assertSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $m): bool => $m->hasTo('buyer.car@example.com'));
        Mail::assertSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $m): bool => $m->hasTo('buyer.accessory@example.com'));
        Mail::assertNotSent(CustomerPromotionMail::class, fn (CustomerPromotionMail $m): bool => $m->hasTo('notbuyer.visited@example.com'));
    }

    public function test_crm_tables_respect_8_query_cold_cache_budget_after_phase_38(): void
    {
        Model::preventLazyLoading(true);

        $admin = $this->createStaff('admin', true);
        $sales = $this->createStaff('sales', false);
        $vehicle = $this->createVehicleProduct();
        $accessory = $this->createAccessoryProduct();

        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Budget Xe',
            'guest_email' => 'budget.car@example.com',
            'guest_phone' => '0901000111',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
                'care_voucher_code' => 'BMW-CARE-BDG38',
            ],
        ]);

        Appointment::query()->create([
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Budget Dịch Vụ',
            'guest_email' => 'budget.srv@example.com',
            'guest_phone' => '0901000222',
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(3)->setTime(14, 0),
        ]);

        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'assigned_to' => $sales->id,
            'customer_name' => 'Khách Budget Phụ Kiện',
            'customer_email' => 'budget.acc@example.com',
            'customer_phone' => '0901000333',
            'customer_address' => '15 Trần Hưng Đạo, Hà Nội',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        foreach ([AppointmentTable::class, ServiceBookingTable::class, AccessoryOrderTable::class] as $componentClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::actingAs($admin)->test($componentClass)->assertStatus(200);

            $queryCount = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(
                8,
                $queryCount,
                "{$componentClass} exceeded 8 cold-cache SQL query budget (ran {$queryCount} queries)."
            );
        }
    }
}
