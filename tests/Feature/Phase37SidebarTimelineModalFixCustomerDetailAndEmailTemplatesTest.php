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
use App\Mail\Customer\TemplatedCustomerMail;
use App\Mail\CustomerPromotionMail;
use App\Models\AccessoryOrder;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\CustomerInstallmentPlan;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Crm\InteractionTimelineRecorder;
use App\Services\Mail\EmailTemplateRenderer;
use App\Support\AdminPageGuideRegistry;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase37SidebarTimelineModalFixCustomerDetailAndEmailTemplatesTest extends TestCase
{
    use RefreshDatabase;

    private function createStaffUser(string $role = 'admin', ?string $name = null): User
    {
        $suffix = uniqid();
        $attrs = ['email' => "{$role}.{$suffix}@bmw.com"];
        if ($name !== null) {
            $attrs['name'] = $name;
        }

        return match ($role) {
            'sales' => User::factory()->sales()->create($attrs),
            'marketing' => User::factory()->marketing()->create($attrs),
            default => User::factory()->topSuperAdmin()->create($attrs),
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

    public function test_item1_crm_tables_stack_status_and_voucher_vertically_and_respect_8_query_cold_cache_budget(): void
    {
        $admin = $this->createStaffUser('admin');
        $sales = $this->createStaffUser('sales');
        $vehicle = $this->createVehicleProduct();
        $accessory = $this->createAccessoryProduct();

        $completedPurchased = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Nguyễn Văn Mua Xe',
            'guest_email' => 'muaxe@example.com',
            'guest_phone' => '0901112233',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
                'care_voucher_code' => 'BMW-CARE-XY789Z',
            ],
        ]);

        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Trần Bảo Dưỡng',
            'guest_email' => 'service@example.com',
            'guest_phone' => '0902223344',
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(3)->setTime(14, 0),
        ]);

        AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'assigned_to' => $sales->id,
            'customer_name' => 'Lê Phụ Kiện',
            'customer_email' => 'acc@example.com',
            'customer_phone' => '0903334455',
            'customer_address' => '123 Nguyễn Văn Linh, Q.7',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        // Cold cache SQL budget check for AppointmentTable
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $component = Livewire::test(AppointmentTable::class);
        $appointmentQueries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(8, $appointmentQueries, "AppointmentTable exceeded 8 SQL queries on cold cache ({$appointmentQueries}).");
        $component
            ->assertSee('Đã mua xe')
            ->assertSee('BMW-CARE-XY789Z')
            ->assertSeeHtml('min-w-[140px] max-w-[180px]')
            ->assertSeeHtml('title="Mã đặc quyền BMW Care: BMW-CARE-XY789Z"');

        // Cold cache SQL budget check for ServiceBookingTable
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(ServiceBookingTable::class)->assertSee('Trần Bảo Dưỡng');
        $serviceQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(8, $serviceQueries, "ServiceBookingTable exceeded 8 SQL queries on cold cache ({$serviceQueries}).");

        // Cold cache SQL budget check for AccessoryOrderTable
        Cache::flush();
        DB::flushQueryLog();
        DB::enableQueryLog();
        Livewire::test(AccessoryOrderTable::class)->assertSee('Lê Phụ Kiện');
        $accessoryQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(8, $accessoryQueries, "AccessoryOrderTable exceeded 8 SQL queries on cold cache ({$accessoryQueries}).");
    }

    public function test_item2_system_received_mail_is_strictly_one_way_noreply_while_admin_confirmed_keeps_reply_to(): void
    {
        $vehicle = $this->createVehicleProduct();
        $appointment = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Phạm Minh Tuấn',
            'guest_email' => 'tuan@example.com',
            'guest_phone' => '0904445566',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);

        // Without MAIL_NOREPLY_ADDRESS configured
        config()->set('mail.noreply_address', null);
        $autoMail = new AppointmentConfirmationMail($appointment, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED);
        $autoEnvelope = $autoMail->envelope();
        $autoHeaders = $autoMail->headers()->text;

        $this->assertSame([], $autoEnvelope->replyTo);
        $this->assertSame('auto-generated', $autoHeaders['Auto-Submitted'] ?? null);
        $this->assertSame('bulk', $autoHeaders['Precedence'] ?? null);
        $this->assertSame('All', $autoHeaders['X-Auto-Response-Suppress'] ?? null);

        $html = $autoMail->render();
        $this->assertStringContainsString('Email tự động 1 chiều — No-Reply', $html);
        $this->assertStringNotContainsString('Quý khách có thể bấm <strong>Trả lời (Reply)</strong> trực tiếp email này', $html);

        // With MAIL_NOREPLY_ADDRESS configured
        config()->set('mail.noreply_address', 'no-reply@bmwshowroom.vn');
        $autoMailWithNoreply = new AppointmentConfirmationMail($appointment, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED);
        $this->assertCount(1, $autoMailWithNoreply->envelope()->replyTo);
        $this->assertSame('no-reply@bmwshowroom.vn', $autoMailWithNoreply->envelope()->replyTo[0]->address);

        // Official admin_confirmed keeps advisor replyTo & Reply hint & omits auto-generated headers
        $confirmedMail = new AppointmentConfirmationMail($appointment, AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED, 'Trần Quang Minh', 'Sales');
        $confirmedEnvelope = $confirmedMail->envelope();
        $confirmedHeaders = $confirmedMail->headers()->text;

        $this->assertCount(1, $confirmedEnvelope->replyTo);
        $this->assertArrayNotHasKey('Auto-Submitted', $confirmedHeaders);
        $this->assertArrayNotHasKey('Precedence', $confirmedHeaders);

        $confirmedHtml = $confirmedMail->render();
        $this->assertStringContainsString('Quý khách có thể bấm <strong>Trả lời (Reply)</strong> trực tiếp email này', $confirmedHtml);
    }

    public function test_item3_admin_sidebar_renders_anti_fouc_collapse_resize_and_role_scoped_email_template_link(): void
    {
        $admin = $this->createStaffUser('admin');
        $marketing = $this->createStaffUser('marketing');
        $sales = $this->createStaffUser('sales');

        $this->actingAs($admin)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertSee('bmw_admin_sidebar_collapsed', false)
            ->assertSee('--admin-sidebar-w', false)
            ->assertSee('data-sidebar-collapse-toggle', false)
            ->assertSee('data-sidebar-resize-handle', false)
            ->assertSee('Kho mẫu Email');

        $this->actingAs($marketing)
            ->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('Kho mẫu Email');

        $this->actingAs($sales)
            ->get(route('admin.customers.index'))
            ->assertOk()
            ->assertDontSee(route('admin.email-templates.index'));

        $guides = AdminPageGuideRegistry::all();
        $this->assertArrayHasKey('admin.customers.show', $guides);
        $this->assertArrayHasKey('admin.email-templates.index', $guides);
        $this->assertArrayHasKey('admin.email-templates.create', $guides);
        $this->assertArrayHasKey('admin.email-templates.edit', $guides);
    }

    public function test_item4_crm_drawers_replace_native_sales_select_with_custom_select_and_lock_when_cancelled(): void
    {
        $admin = $this->createStaffUser('admin');
        $sales = $this->createStaffUser('sales');
        $vehicle = $this->createVehicleProduct();
        $accessory = $this->createAccessoryProduct();

        $cancelledAppointment = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Hủy Lịch',
            'guest_email' => 'cancel@example.com',
            'guest_phone' => '0905556677',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Cancelled->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);

        $activeOrder = AccessoryOrder::query()->create([
            'product_id' => $accessory->id,
            'assigned_to' => $sales->id,
            'customer_name' => 'Khách Phụ Kiện',
            'customer_email' => 'order@example.com',
            'customer_phone' => '0906667788',
            'customer_address' => '123 Lê Lợi',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $this->actingAs($admin);

        Livewire::test(AppointmentTable::class)
            ->call('inspectAppointment', $cancelledAppointment->id)
            ->assertSeeHtml('Phân công Sales cho lịch hẹn #'.$cancelledAppointment->id)
            ->assertSeeHtml('data-disabled="true"')
            ->assertDontSeeHtml('wire:change="assignToSales(');

        Livewire::test(AccessoryOrderTable::class)
            ->call('inspectOrder', $activeOrder->id)
            ->assertSeeHtml('Phân công Sales cho đơn phụ kiện #'.$activeOrder->id)
            ->assertDontSeeHtml('wire:change="assignToSales(');
    }

    public function test_item5_interaction_timeline_groups_events_into_lossless_lifecycle_cycles(): void
    {
        $events = [
            InteractionTimelineRecorder::makeEvent('customer', 'Khách hàng', 'Khách gửi yêu cầu đặt lịch', 'Chọn BMW X5', 'Web', now()->subHours(5)),
            InteractionTimelineRecorder::makeEvent('system', 'Hệ thống tự động', 'Đã gửi thư tiếp nhận tự động', 'No-Reply', 'Email', now()->subHours(5)->addMinute()),
            InteractionTimelineRecorder::makeEvent('customer', 'Khách hàng', 'Khách hàng tự đổi lịch hẹn', 'Dời sang 15:00 ngày mai', 'Guest Manage', now()->subHours(3)),
            InteractionTimelineRecorder::makeEvent('system', 'Hệ thống tự động', 'Đã thông báo Sales về việc khách đổi lịch', 'Email nội bộ', 'Email', now()->subHours(3)->addMinute()),
            InteractionTimelineRecorder::makeEvent('admin', 'Sales · Minh', 'Chuyên viên đã tiếp nhận đổi lịch', 'Xác nhận giờ mới', 'CRM', now()->subHours(2)),
            InteractionTimelineRecorder::makeEvent('admin', 'Sales · Minh', 'Hoàn tất đón tiếp & khách đã mua xe', 'Voucher BMW-CARE-ABC123', 'CRM', now()->subHour()),
        ];

        $cycles = InteractionTimelineRecorder::groupEventsIntoLifecycles($events);

        $this->assertCount(3, $cycles);
        $this->assertSame('intake', $cycles[0]['kind']);
        $this->assertSame('reschedule', $cycles[1]['kind']);
        $this->assertSame('closure', $cycles[2]['kind']);

        $totalPreserved = array_sum(array_column($cycles, 'event_count'));
        $this->assertSame(count($events), $totalPreserved, 'Timeline grouping must never drop any event.');
        $this->assertFalse($cycles[0]['is_latest']);
        $this->assertFalse($cycles[1]['is_latest']);
        $this->assertTrue($cycles[2]['is_latest']);
        $this->assertStringContainsString('Khách hàng → Hệ thống → Chuyên viên', $cycles[1]['flow_summary']);

        // Render inside AppointmentTable Drawer and verify timeline component attributes & all logs remain visible in DOM
        $admin = $this->createStaffUser('admin');
        $vehicle = $this->createVehicleProduct();
        $appointment = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Timeline',
            'guest_email' => 'timeline@example.com',
            'guest_phone' => '0907778899',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);
        $appointment->forceFill(['meta_data' => ['timeline' => $events]])->saveQuietly();

        $this->actingAs($admin);
        Livewire::test(AppointmentTable::class)
            ->call('inspectAppointment', $appointment->id)
            ->assertSeeHtml('data-crm-interaction-timeline')
            ->assertSee('6 sự kiện · 3 chu kỳ')
            ->assertSee('Chu kỳ 2: Khách đổi lịch ↔ Hệ thống ↔ Chuyên viên')
            ->assertSee('Khách gửi yêu cầu đặt lịch')
            ->assertSee('Voucher BMW-CARE-ABC123');
    }

    public function test_item6_completion_outcome_modal_starts_unselected_validates_strictly_and_supports_both_outcomes(): void
    {
        Mail::fake();

        $admin = $this->createStaffUser('admin');
        $vehicle = $this->createVehicleProduct();

        $appointmentVisited = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Tham Quan',
            'guest_email' => 'visited@example.com',
            'guest_phone' => '0908881122',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(1)->setTime(10, 0),
        ]);

        $appointmentPurchased = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Chốt Xe',
            'guest_email' => 'buyer@example.com',
            'guest_phone' => '0908883344',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(1)->setTime(15, 0),
        ]);

        $this->actingAs($admin);

        // 1. Opening modal leaves completionOutcome empty and blocks premature submission
        Livewire::test(AppointmentTable::class)
            ->call('openCompletionModal', $appointmentVisited->id)
            ->assertSet('completionTargetId', $appointmentVisited->id)
            ->assertSet('completionOutcome', '')
            ->assertSee('Chưa chọn kết quả')
            ->call('confirmCompletion')
            ->assertHasErrors(['completionOutcome'])
            ->assertSet('completionTargetId', $appointmentVisited->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_VISITED_NO_PURCHASE)
            ->assertSet('completionOutcome', Appointment::OUTCOME_VISITED_NO_PURCHASE)
            ->call('confirmCompletion')
            ->assertSet('completionTargetId', null);

        $appointmentVisited->refresh();
        $this->assertSame(AppointmentStatus::Completed, $appointmentVisited->status);
        $this->assertSame(Appointment::OUTCOME_VISITED_NO_PURCHASE, $appointmentVisited->completionOutcome());
        $this->assertNull($appointmentVisited->careVoucherCode());

        // 2. Selecting purchased generates BMW-CARE voucher
        Livewire::test(AppointmentTable::class)
            ->call('openCompletionModal', $appointmentPurchased->id)
            ->call('selectCompletionOutcome', Appointment::OUTCOME_PURCHASED)
            ->call('confirmCompletion')
            ->assertSet('completionTargetId', null);

        $appointmentPurchased->refresh();
        $this->assertSame(AppointmentStatus::Completed, $appointmentPurchased->status);
        $this->assertSame(Appointment::OUTCOME_PURCHASED, $appointmentPurchased->completionOutcome());
        $this->assertMatchesRegularExpression('/^BMW-CARE-[A-Z0-9]{6}$/', (string) $appointmentPurchased->careVoucherCode());

        Mail::assertQueued(AppointmentCompletedThankYouMail::class, 2);
    }

    public function test_item7_customer_360_detail_installment_plans_sales_scoping_and_quick_email_serialization(): void
    {
        Mail::fake();
        $this->seed(EmailTemplateSeeder::class);

        $admin = $this->createStaffUser('admin');
        $salesOwner = $this->createStaffUser('sales', 'Sales Chủ Hồ Sơ');
        $salesOther = $this->createStaffUser('sales', 'Sales Khác');
        $vehicle = $this->createVehicleProduct('BMW 530i M Sport');

        $appointment = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'assigned_to' => $salesOwner->id,
            'guest_name' => 'Nguyễn Hoàng Nam',
            'guest_email' => 'nam.bmw@example.com',
            'guest_phone' => '0909 123 456',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(2)->setTime(10, 0),
        ]);

        // Unassigned Sales receives 403 Forbidden (cross-sales isolation)
        $this->actingAs($salesOther)
            ->get(route('admin.customers.show', ['phone' => '0909123456', 'email' => 'nam.bmw@example.com']))
            ->assertForbidden();

        // Assigned Sales can access Customer 360° via normalized phone + ?email=
        $this->actingAs($salesOwner)
            ->get(route('admin.customers.show', ['phone' => '0909123456', 'email' => 'nam.bmw@example.com']))
            ->assertOk()
            ->assertSee('Nguyễn Hoàng Nam')
            ->assertSee('BMW 530i M Sport');

        // Create Installment Plan
        $this->actingAs($salesOwner)
            ->post(route('admin.customers.installments.store', ['phone' => '0909123456', 'email' => 'nam.bmw@example.com']), [
                'route_email' => 'nam.bmw@example.com',
                'bank_name' => 'Techcombank BMW Financial',
                'product_id' => $vehicle->id,
                'appointment_id' => $appointment->id,
                'loan_amount' => 2200000000,
                'monthly_payment' => 45000000,
                'interest_rate' => 7.85,
                'term_months' => 60,
                'paid_installments' => 12,
                'next_due_date' => '2026-12-15',
                'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
                'notes' => 'Cố định 12 tháng đầu',
            ])
            ->assertRedirect();

        $plan = CustomerInstallmentPlan::query()->firstOrFail();
        $this->assertSame('0909123456', $plan->customer_phone);
        $this->assertSame(12, $plan->paid_installments);

        // Update Installment Plan progress & interest rate
        $this->actingAs($admin)
            ->patch(route('admin.customers.installments.update', ['phone' => '0909123456', 'plan' => $plan->id]), [
                'route_email' => 'nam.bmw@example.com',
                'bank_name' => 'Techcombank BMW Financial',
                'loan_amount' => 2200000000,
                'monthly_payment' => 44500000,
                'interest_rate' => 7.5,
                'term_months' => 60,
                'paid_installments' => 13,
                'next_due_date' => '2027-01-15',
                'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
                'notes' => 'Đã điều chỉnh giảm lãi suất',
            ])
            ->assertRedirect();

        $plan->refresh();
        $this->assertSame(13, $plan->paid_installments);
        $this->assertEquals(7.5, $plan->interest_rate);

        // Draft template must be rejected in 1-to-1 quick email
        $draftTemplate = EmailTemplate::query()->create([
            'code' => 'draft-only-test',
            'name' => 'Bản nháp chưa duyệt',
            'category' => EmailTemplate::CATEGORY_PROMOTION,
            'subject' => 'Nháp {{customer_name}}',
            'heading' => 'Nháp',
            'body_content' => 'Nội dung nháp',
            'accent_color' => '#1C69D4',
            'status' => EmailTemplate::STATUS_DRAFT,
        ]);

        $this->actingAs($salesOwner)
            ->post(route('admin.customers.send-email', ['phone' => '0909123456']), [
                'route_email' => 'nam.bmw@example.com',
                'template_id' => $draftTemplate->id,
                'recipient_email' => 'nam.bmw@example.com',
            ])
            ->assertSessionHasErrors('template_id');

        // Active template sends TemplatedCustomerMail cleanly & survives serialize/unserialize under strict preventLazyLoading
        $activeTemplate = EmailTemplate::query()->where('code', 'installment-due-reminder')->firstOrFail();

        $this->actingAs($salesOwner)
            ->post(route('admin.customers.send-email', ['phone' => '0909123456']), [
                'route_email' => 'nam.bmw@example.com',
                'template_id' => $activeTemplate->id,
                'installment_plan_id' => $plan->id,
                'recipient_email' => 'nam.bmw@example.com',
            ])
            ->assertRedirect();

        Mail::assertQueued(TemplatedCustomerMail::class, function (TemplatedCustomerMail $mail): bool {
            $wasStrict = Model::preventsLazyLoading();
            Model::preventLazyLoading(true);
            try {
                /** @var TemplatedCustomerMail $rehydrated */
                $rehydrated = unserialize(serialize($mail));
                $renderedHtml = $rehydrated->render();
                $subject = $rehydrated->envelope()->subject;

                $this->assertSame('notifications', $rehydrated->queue);
                $this->assertStringContainsString('Nguyễn Hoàng Nam', $renderedHtml);
                $this->assertStringContainsString('Techcombank BMW Financial', $renderedHtml);
                $this->assertStringContainsString('7.5%/năm', $renderedHtml);
                $this->assertStringContainsString('15/01/2027', $renderedHtml);
                $this->assertStringNotContainsString('{{', $renderedHtml);
                $this->assertStringNotContainsString('}}', $renderedHtml);
                $this->assertStringNotContainsString('{{', $subject);

                return true;
            } finally {
                Model::preventLazyLoading($wasStrict);
            }
        });
    }

    public function test_item8_email_template_studio_rbac_crud_and_installment_campaign_interpolation(): void
    {
        Mail::fake();
        $this->seed(EmailTemplateSeeder::class);
        // Re-running seeder must stay idempotent (still 3 records)
        $this->seed(EmailTemplateSeeder::class);
        $this->assertSame(3, EmailTemplate::query()->count());

        $marketing = $this->createStaffUser('marketing');
        $sales = $this->createStaffUser('sales');
        $vehicle = $this->createVehicleProduct('BMW 735i Pure Excellence');

        // Sales is strictly blocked (403) from Email Template Studio
        $this->actingAs($sales)->get(route('admin.email-templates.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.email-templates.create'))->assertForbidden();

        // Marketing can create, update, duplicate, and archive/activate templates
        $this->actingAs($marketing)
            ->post(route('admin.email-templates.store'), [
                'name' => 'Thông báo ưu đãi quý 4',
                'code' => 'promo-q4-vip',
                'category' => EmailTemplate::CATEGORY_PROMOTION,
                'status' => EmailTemplate::STATUS_ACTIVE,
                'subject' => 'Ưu đãi Q4 dành cho {{customer_name}}',
                'eyebrow' => 'BMW VIP Club',
                'heading' => 'Chương trình tri ân Q4',
                'body_content' => 'Kính chào {{customer_name}}, xe {{vehicle_name}} có ưu đãi mới.',
                'accent_color' => '#1C69D4',
            ])
            ->assertRedirect();

        $created = EmailTemplate::query()->where('code', 'promo-q4-vip')->firstOrFail();

        $this->actingAs($marketing)
            ->post(route('admin.email-templates.duplicate', $created))
            ->assertRedirect();

        $this->assertDatabaseHas('email_templates', [
            'code' => 'promo-q4-vip-copy',
            'status' => EmailTemplate::STATUS_DRAFT,
        ]);

        // Test Campaign Center audience = installment + per-recipient interpolation with zero {{...}} leakage
        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Trả Góp',
            'guest_email' => 'installment.vip@example.com',
            'guest_phone' => '0911223344',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(5)->setTime(10, 0),
        ]);

        Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Tiền Mặt',
            'guest_email' => 'cash.only@example.com',
            'guest_phone' => '0911998877',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDays(4)->setTime(10, 0),
        ]);

        CustomerInstallmentPlan::query()->create([
            'customer_phone' => '0911223344',
            'customer_email' => 'installment.vip@example.com',
            'customer_name' => 'Khách Trả Góp',
            'product_id' => $vehicle->id,
            'bank_name' => 'VPBank BMW Auto',
            'loan_amount' => 3000000000,
            'monthly_payment' => 55000000,
            'interest_rate' => 8.2,
            'term_months' => 60,
            'paid_installments' => 6,
            'next_due_date' => '2026-12-20',
            'status' => CustomerInstallmentPlan::STATUS_ACTIVE,
        ]);

        // Run listener directly on PromotionCampaignDispatched to assert rendered CustomerPromotionMail interpolation
        $listener = new SendPromotionCampaignEmails;
        $listener->handle(new PromotionCampaignDispatched(
            campaignId: 'phase37-test-campaign',
            offerTitle: 'Điều chỉnh lãi suất {{interest_rate}} cho {{customer_name}}',
            offerContent: 'Chào {{customer_name}}, hợp đồng tại {{bank_name}} cho xe {{vehicle_name}} có kỳ hạn kế tiếp {{next_due_date}} với số tiền {{monthly_payment}} và biến lạ {{unknown_var}}.',
            recipients: [
                [
                    'email' => 'installment.vip@example.com',
                    'name' => 'Khách Trả Góp',
                    'vehicle_name' => 'BMW 735i Pure Excellence',
                ],
                [
                    'email' => 'cash.only@example.com',
                    'name' => 'Khách Tiền Mặt',
                    'vehicle_name' => 'BMW 735i Pure Excellence',
                ],
            ],
        ));

        Mail::assertSent(CustomerPromotionMail::class, 2);
        Mail::assertSent(CustomerPromotionMail::class, function (CustomerPromotionMail $mail): bool {
            if (! $mail->hasTo('installment.vip@example.com')) {
                return false;
            }
            $html = $mail->render();
            $this->assertStringContainsString('VPBank BMW Auto', $html);
            $this->assertStringContainsString('8.2%/năm', $html);
            $this->assertStringContainsString('20/12/2026', $html);
            $this->assertStringContainsString('55.000.000 VNĐ', $html);
            $this->assertStringNotContainsString('{{', $html);
            $this->assertStringNotContainsString('}}', $html);

            return true;
        });

        // Customer without installment plan still receives clean fallback with ZERO raw {{...}}
        Mail::assertSent(CustomerPromotionMail::class, function (CustomerPromotionMail $mail): bool {
            if (! $mail->hasTo('cash.only@example.com')) {
                return false;
            }
            $html = $mail->render();
            $this->assertStringContainsString('Ngân hàng đối tác BMW Financial', $html);
            $this->assertStringNotContainsString('{{', $html);
            $this->assertStringNotContainsString('}}', $html);

            return true;
        });

        // Verify POST /admin/customers/promotions with audience=installment targets ONLY the installment customer
        $this->actingAs($marketing)
            ->post(route('admin.customers.promotions.store'), [
                'title' => 'Nhắc lịch trả góp {{next_due_date}}',
                'content' => 'Kính gửi {{customer_name}}, lãi suất hiện tại {{interest_rate}}.',
                'audience' => 'installment',
            ])
            ->assertRedirect(route('admin.customers.index'))
            ->assertSessionHas('success', 'Đã phát chiến dịch ưu đãi tới 1 khách hàng mục tiêu.');
    }

    public function test_renderer_fallback_never_leaks_unrecognized_or_missing_placeholders(): void
    {
        $renderer = app(EmailTemplateRenderer::class);
        $vars = $renderer->buildVariables();
        $output = $renderer->interpolate(
            'Xin chào {{customer_name}}, xe {{vehicle_name}}, ngân hàng {{bank_name}}, lãi {{interest_rate}}, ngày {{next_due_date}}, lạ {{non_existent_key}}.',
            $vars
        );

        $this->assertStringContainsString('Quý khách', $output);
        $this->assertStringContainsString('dòng xe BMW', $output);
        $this->assertStringNotContainsString('{{', $output);
        $this->assertStringNotContainsString('}}', $output);
    }

    public function test_regression_e2e_fixes_studio_routes_noreply_header_stripping_and_loop_variable_isolation(): void
    {
        $this->seed(EmailTemplateSeeder::class);
        $marketing = $this->createStaffUser('marketing');
        $admin = $this->createStaffUser('admin');
        $vehicle = $this->createVehicleProduct();

        // 1. /admin/email-templates/create and /edit must render cleanly with 200 OK (escaped @{{vehicle_name}})
        $template = EmailTemplate::query()->firstOrFail();
        $this->actingAs($marketing)
            ->get(route('admin.email-templates.create'))
            ->assertOk()
            ->assertSee('bmw-email-template-form-config', false);

        $this->actingAs($marketing)
            ->get(route('admin.email-templates.edit', $template))
            ->assertOk()
            ->assertSee('bmwEmailTemplateForm', false);

        // 2. Customer 360 quick-email config must use clean JSON script tag (no literal @js inside x-data attribute)
        $purchased = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Đã Mua Loop',
            'guest_email' => 'loop.purchased@example.com',
            'guest_phone' => '0909111000',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
        ]);
        $purchased->forceFill([
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
                'care_voucher_code' => 'BMW-CARE-Z9Z9Z9',
            ],
        ])->saveQuietly();

        $confirmed = Appointment::query()->create([
            'product_id' => $vehicle->id,
            'guest_name' => 'Khách Chờ Chốt Modal',
            'guest_email' => 'modal.target@example.com',
            'guest_phone' => '0909222000',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(3)->setTime(14, 0),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.customers.show', ['phone' => '0909222000', 'email' => 'modal.target@example.com']))
            ->assertOk()
            ->assertSee('bmw-quick-email-config', false)
            ->assertDontSee('templates: @js(', false);

        // 3. Completion Outcome Modal stays unselected even when another completed+purchased row exists in the same table loop
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->call('openCompletionModal', $confirmed->id)
            ->assertSet('completionOutcome', '')
            ->assertSee('Chưa chọn kết quả')
            ->assertSeeHtml('data-outcome-option="purchased"')
            ->assertDontSeeHtml('Đang chọn:</span> Khách đã mua xe');

        // 4. Real sent Symfony message for MODE_SYSTEM_RECEIVED removes global config Reply-To header
        config()->set('mail.reply_to.address', 'advisor@bmw.com');
        config()->set('mail.noreply_address', null);

        $sentAuto = Mail::mailer('array')->to('modal.target@example.com')->sendNow(
            new AppointmentConfirmationMail($confirmed, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED)
        );
        $this->assertNotNull($sentAuto);
        $this->assertFalse(
            $sentAuto->getOriginalMessage()->getHeaders()->has('Reply-To'),
            'MODE_SYSTEM_RECEIVED must strip global Reply-To header when MAIL_NOREPLY_ADDRESS is unset.'
        );
    }
}
