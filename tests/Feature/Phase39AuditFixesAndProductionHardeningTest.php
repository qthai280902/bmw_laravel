<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AccessoryOrderTable;
use App\Livewire\Admin\AppointmentTable;
use App\Livewire\Admin\ServiceBookingTable;
use App\Mail\AppointmentReminderMail;
use App\Mail\Customer\AppointmentCompletedThankYouMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\Customer\PostTestDriveFollowupMail;
use App\Mail\Customer\TemplatedCustomerMail;
use App\Mail\CustomerPromotionMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\ShowroomAssistantService;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class Phase39AuditFixesAndProductionHardeningTest extends TestCase
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
                : User::factory()->create(array_merge($attrs, [
                    'role' => User::ROLE_ADMIN,
                    'is_super_admin' => false,
                ])),
        };
    }

    private function createCarProduct(string $name = 'BMW 330i M Sport'): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW Sedan '.uniqid(),
            'slug' => 'bmw-sedan-'.uniqid(),
        ]);

        return Product::factory()->create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => 'bmw-330i-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'deposit_amount' => 50000000,
            'is_active' => true,
        ]);
    }

    public function test_sales_customer_index_is_strictly_scoped_to_assigned_customers_hides_sales_filter_and_renders_utf8_avatar_and_valid_autocomplete_markup(): void
    {
        $topAdmin = $this->createStaff('admin', true, 'Đặng Quản Trị Tối Cao');
        $salesA = $this->createStaff('sales', false, 'Nguyễn Tư Vấn Alpha');
        $salesB = $this->createStaff('sales', false, 'Trần Tư Vấn Beta');
        $car = $this->createCarProduct();

        Appointment::query()->create([
            'product_id' => $car->id,
            'assigned_to' => $salesA->id,
            'guest_name' => 'Đặng Văn Khách Alpha',
            'guest_email' => 'alpha.owned@example.com',
            'guest_phone' => '0911222333',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Completed->value,
            'appointment_date' => now()->subDay(),
            'meta_data' => [
                'completion_outcome' => Appointment::OUTCOME_PURCHASED,
            ],
        ]);

        Appointment::query()->create([
            'product_id' => $car->id,
            'assigned_to' => $salesB->id,
            'guest_name' => 'Phạm Thị Khách Beta',
            'guest_email' => 'beta.secret@example.com',
            'guest_phone' => '0944555666',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDay(),
        ]);

        $unassigned = Appointment::query()->create([
            'product_id' => $car->id,
            'assigned_to' => null,
            'guest_name' => 'Lê Vãng Lai Chưa Gán',
            'guest_email' => 'unassigned.secret@example.com',
            'guest_phone' => '0988777666',
            'type' => AppointmentType::Consult->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2),
        ]);
        $unassigned->forceFill(['assigned_to' => null])->saveQuietly();

        // Sales A only sees their own customer in /admin/customers even if spoofing ?sales_id=salesB or unassigned
        $salesResponse = $this->actingAs($salesA)
            ->get(route('admin.customers.index', ['sales_id' => $salesB->id]));

        $salesResponse->assertOk();
        $customersPaginator = $salesResponse->viewData('customers');
        $this->assertSame(1, $customersPaginator->total());
        $this->assertSame(['alpha.owned@example.com'], collect($customersPaginator->items())->pluck('email')->all());

        $salesHtml = $salesResponse->getContent();
        preg_match('/<main\b[^>]*>(.*)<\/main>/s', $salesHtml, $mainMatches);
        $salesMainHtml = $mainMatches[1] ?? $salesHtml;

        $this->assertStringContainsString('Đặng Văn Khách Alpha', $salesMainHtml);
        $this->assertStringContainsString('alpha.owned@example.com', $salesMainHtml);
        $this->assertStringNotContainsString('Phạm Thị Khách Beta', $salesMainHtml);
        $this->assertStringNotContainsString('beta.secret@example.com', $salesMainHtml);
        $this->assertStringNotContainsString('0944555666', $salesMainHtml);
        $this->assertStringNotContainsString('Lê Vãng Lai Chưa Gán', $salesMainHtml);
        $this->assertStringNotContainsString('unassigned.secret@example.com', $salesMainHtml);
        $this->assertStringNotContainsString('id="customer-filter-sales"', $salesMainHtml);
        $this->assertStringNotContainsString("\u{FFFD}", $salesMainHtml, 'UTF-8 Vietnamese initial Đ must not produce replacement glyph.');
        $this->assertStringContainsString('>Đ</span>', $salesMainHtml);

        // Top Super Admin sees all 3 customers, Sales filter, and valid phrasing markup inside autocomplete <button>
        $adminResponse = $this->actingAs($topAdmin)->get(route('admin.customers.index'));
        $adminResponse->assertOk();
        $adminHtml = $adminResponse->getContent();

        $this->assertStringContainsString('Phạm Thị Khách Beta', $adminHtml);
        $this->assertStringContainsString('id="customer-filter-sales"', $adminHtml);
        $this->assertMatchesRegularExpression(
            '/<template\s+x-for="item in suggestions"[^>]*>\s*<button[^>]*>(?:(?!<div|<p\b).)*?<\/button>\s*<\/template>/s',
            $adminHtml,
            'Purchased-customer autocomplete <button> must use phrasing <span> elements without <div or <p children.'
        );
    }

    public function test_ai_stream_truncated_3_word_cutoff_and_mid_stream_drop_emit_fallback_with_replace_text_and_widget_hides_tooltip_on_booking(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'test-gemini-key-1',
        ]);

        // Case 1: Stream prematurely terminates after 3 words ("Quý khách thân") without terminal punctuation
        ShowroomAssistant::fake([
            'Quý khách thân',
        ]);

        $streamResp = $this->post(route('ai.showroom-assistant.stream'), [
            'message' => 'Tư vấn BMW Series 3 giá bao nhiêu?',
            'visitor_id' => 'visitor-phase39-trunc-1',
        ]);

        $streamResp->assertOk();
        $sse = $streamResp->streamedContent();

        $this->assertStringContainsString("event: chunk\n", $sse);
        $this->assertStringContainsString("event: done\n", $sse);
        $this->assertStringContainsString('"status":"fallback"', $sse);
        $this->assertStringContainsString('"reason":"stream_truncated"', $sse);
        $this->assertStringContainsString('"replace_text":true', $sse);
        $this->assertStringContainsString(json_encode(config('showroom_ai.fallback_message'), JSON_UNESCAPED_UNICODE), $sse);

        $savedAssistantMsg = AiChatMessage::query()
            ->where('role', 'assistant')
            ->latest('id')
            ->first();

        $this->assertNotNull($savedAssistantMsg);
        $this->assertSame(config('showroom_ai.fallback_message'), $savedAssistantMsg->content);
        $this->assertSame('stream_truncated', $savedAssistantMsg->response_reason);
        $this->assertNotSame('Quý khách thân', $savedAssistantMsg->content);

        // Case 2: Widget hides greeting bubble on /booking & guest-manage, shows on /, and removes duplicate x-init="init()"
        $car = $this->createCarProduct();
        $homeHtml = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('data-ai-greeting-bubble', $homeHtml);
        $this->assertStringNotContainsString('x-init="init()"', $homeHtml);

        $bookingHtml = $this->get('/booking')->assertOk()->getContent();
        $this->assertStringNotContainsString('data-ai-greeting-bubble', $bookingHtml);

        $appt = Appointment::query()->create([
            'product_id' => $car->id,
            'guest_name' => 'Khách Tự Phục Vụ',
            'guest_email' => 'guest.manage39@example.com',
            'guest_phone' => '0933111222',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(3)->setTime(10, 0),
        ]);
        $manageHtml = $this->get($appt->temporaryManageUrl())->assertOk()->getContent();
        $this->assertStringNotContainsString('data-ai-greeting-bubble', $manageHtml);
    }

    public function test_real_gemini_http_stream_mid_cutoff_replaces_partial_chunk_with_fallback(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'test-gemini-key-real-http',
            'showroom_ai.gemini_keys.additional' => '',
        ]);

        Http::fake([
            '*generativelanguage.googleapis.com/*' => Http::response(
                "data: {\"candidates\":[{\"content\":{\"parts\":[{\"text\":\"Quý khách thân\"}]}}]}\n\n",
                200,
                ['Content-Type' => 'text/event-stream']
            ),
        ]);

        $service = app(ShowroomAssistantService::class);
        $events = iterator_to_array($service->stream('Báo giá BMW 330i', ['visitor_id' => 'visitor-phase39-trunc-2']));
        $doneEvents = array_values(array_filter($events, static fn (array $e): bool => ($e['event'] ?? '') === 'done'));

        $this->assertNotEmpty($doneEvents);
        $donePayload = $doneEvents[0]['data'] ?? [];
        $this->assertSame('fallback', $donePayload['status'] ?? null);
        $this->assertTrue((bool) ($donePayload['replace_text'] ?? false));
        $this->assertSame(config('showroom_ai.fallback_message'), $donePayload['answer'] ?? null);
    }

    public function test_reply_to_header_is_never_duplicated_in_mime_across_all_mailables_and_system_received_strips_reply_to(): void
    {
        $this->seed(EmailTemplateSeeder::class);

        config([
            'mail.default' => 'array',
            'mail.from.address' => 'showroom@bmw.com',
            'mail.from.name' => 'BMW Showroom',
            'mail.reply_to.address' => 'advisor@bmw.com',
            'mail.reply_to.name' => 'BMW Showroom Tư Vấn',
            'mail.showroom_admin' => 'advisor@bmw.com',
            'mail.showroom_admins' => ['advisor@bmw.com'],
            'mail.noreply_address' => null,
        ]);

        $sales = $this->createStaff('sales', false, 'Nguyễn Cố Vấn');
        $car = $this->createCarProduct();
        $appt = Appointment::query()->create([
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Kiểm Tra MIME',
            'guest_email' => 'mime.check@example.com',
            'guest_phone' => '0966888999',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(2)->setTime(9, 30),
        ]);
        $template = EmailTemplate::query()->firstOrFail();

        $transport = Mail::mailer('array')->getSymfonyTransport();
        $transport->flush();

        // 1. MODE_SYSTEM_RECEIVED -> strictly 0 Reply-To
        Mail::mailer('array')->to('mime.check@example.com')->send(
            new AppointmentConfirmationMail($appt, AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED)
        );
        $sentAuto = $transport->messages()->first();
        $this->assertNotNull($sentAuto);
        $this->assertFalse($sentAuto->getOriginalMessage()->getHeaders()->has('Reply-To'));

        // 2. All 7 reply-enabled Mailables -> strictly 1 Reply-To address (zero duplication)
        $replyMailables = [
            new AppointmentConfirmationMail($appt, AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED, $sales->name, $sales->roleLabel()),
            new AppointmentCompletedThankYouMail($appt, AppointmentCompletedThankYouMail::OUTCOME_PURCHASED, 'BMW-CARE-39TEST'),
            new CustomerPromotionMail('Khách Kiểm Tra MIME', $car->name, 'Ưu đãi Đặc Quyền', 'Nội dung chi tiết ưu đãi.'),
            new TemplatedCustomerMail($template, ['customer_name' => 'Khách Kiểm Tra MIME', 'vehicle_name' => $car->name]),
            new AppointmentReminderMail($appt),
            new PostTestDriveFollowupMail($appt),
            new GuestAppointmentChangeMail($appt, GuestAppointmentChangeMail::ACTION_RESCHEDULE),
        ];

        foreach ($replyMailables as $mailable) {
            $transport->flush();
            Mail::mailer('array')->to('mime.check@example.com')->send($mailable);
            $sent = $transport->messages()->first();
            $this->assertNotNull($sent, 'Expected sent message for '.$mailable::class);

            $original = $sent->getOriginalMessage();
            $replyToList = $original->getReplyTo();
            $this->assertCount(1, $replyToList, 'Expected exactly 1 Reply-To address on '.$mailable::class);
            $this->assertSame('advisor@bmw.com', $replyToList[0]->getAddress());

            $mailableClass = $mailable::class;
            $rawHeader = (string) $original->getHeaders()->get('Reply-To')?->toString();
            $this->assertSame(
                1,
                substr_count(strtolower($rawHeader), 'advisor@bmw.com'),
                "Duplicate Reply-To header detected on {$mailableClass}: {$rawHeader}"
            );
        }
    }

    public function test_archive_modal_z_index_teleport_immediate_drawer_mail_sync_dashboard_accents_and_production_configs(): void
    {
        // 1. Archive modal uses x-teleport="body" and z-[120] above Quick Inspector Drawer (z-[100]/z-[101])
        $modalHtml = Blade::render('<x-admin.confirm-archive-modal action="archive" reference-code="BMW-261125-0001" />');
        $this->assertStringContainsString('x-teleport="body"', $modalHtml);
        $this->assertStringContainsString('z-[120]', $modalHtml);
        $this->assertStringNotContainsString('z-[80]', $modalHtml);

        // 2. Acknowledging guest reschedule immediately flips row mail badge to ĐÃ GỬI MAIL XÁC NHẬN without reload
        Mail::fake();
        $topAdmin = $this->createStaff('admin', true);
        $car = $this->createCarProduct();
        $rescheduledAppt = Appointment::query()->create([
            'product_id' => $car->id,
            'guest_name' => 'Khách Đổi Lịch Livewire',
            'guest_email' => 'reschedule.sync@example.com',
            'guest_phone' => '0977123456',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDays(4)->setTime(15, 0),
            'confirmation_email_sent_at' => null,
            'meta_data' => [
                'reschedule_pending_review' => true,
                'manage_version' => 2,
            ],
        ]);

        Livewire::actingAs($topAdmin)
            ->test(AppointmentTable::class)
            ->assertSee('CHƯA GỬI MAIL')
            ->call('inspectAppointment', $rescheduledAppt->id)
            ->call('acknowledgeGuestReschedule', $rescheduledAppt->id)
            ->assertSee('ĐÃ GỬI MAIL XÁC NHẬN');

        // 3. Dashboard renders properly accented Vietnamese subtitles
        $dashboardHtml = $this->actingAs($topAdmin)->get(route('dashboard'))->assertOk()->getContent();
        $this->assertStringContainsString('Khách có nhu cầu đổi xe cũ lên BMW', $dashboardHtml);
        $this->assertStringContainsString('Bảo dưỡng và chăm sóc xe BMW', $dashboardHtml);
        $this->assertStringNotContainsString('Khach co nhu cau doi xe cu len BMW', $dashboardHtml);

        // 4. Production Docker, Nginx & PHP hardening assertions
        $compose = (string) file_get_contents(base_path('docker-compose.yml'));
        $this->assertStringContainsString('127.0.0.1:${FORWARD_DB_PORT:-3307}:3306', $compose);
        $this->assertStringContainsString('DB_HOST: mysql', $compose);
        $this->assertStringContainsString('AI_ASSISTANT_TIMEOUT_SECONDS:', $compose);

        $nginx = (string) file_get_contents(base_path('docker/nginx/default.conf'));
        $this->assertStringContainsString('server_tokens off;', $nginx);
        $this->assertStringContainsString('X-Frame-Options "SAMEORIGIN"', $nginx);
        $this->assertStringContainsString('X-Content-Type-Options "nosniff"', $nginx);
        $this->assertStringContainsString('Referrer-Policy "strict-origin-when-cross-origin"', $nginx);

        $phpIni = (string) file_get_contents(base_path('docker/php/php.ini'));
        $this->assertStringContainsString('expose_php=Off', $phpIni);
    }

    public function test_cold_cache_sql_query_budget_and_prevent_lazy_loading_preserved_on_all_three_crm_tables(): void
    {
        $topAdmin = $this->createStaff('admin', true);
        $sales = $this->createStaff('sales', false);
        $car = $this->createCarProduct();

        Appointment::query()->create([
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Xe CRM',
            'guest_email' => 'crm.car@example.com',
            'guest_phone' => '0909111222',
            'type' => AppointmentType::TestDrive->value,
            'status' => AppointmentStatus::Confirmed->value,
            'appointment_date' => now()->addDay(),
        ]);

        Appointment::query()->create([
            'assigned_to' => $sales->id,
            'guest_name' => 'Khách Xưởng CRM',
            'guest_email' => 'crm.service@example.com',
            'guest_phone' => '0909333444',
            'type' => AppointmentType::Maintenance->value,
            'status' => AppointmentStatus::Pending->value,
            'appointment_date' => now()->addDays(2),
            'meta_data' => ['service_package' => 'Bảo dưỡng tiêu chuẩn'],
        ]);

        AccessoryOrder::query()->create([
            'product_id' => $car->id,
            'assigned_to' => $sales->id,
            'customer_name' => 'Khách Phụ Kiện CRM',
            'customer_email' => 'crm.acc@example.com',
            'customer_phone' => '0909555666',
            'customer_address' => 'Quận 1, TP.HCM',
            'quantity' => 1,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        Model::preventLazyLoading(true);
        $this->actingAs($topAdmin);

        foreach ([
            'AppointmentTable' => AppointmentTable::class,
            'ServiceBookingTable' => ServiceBookingTable::class,
            'AccessoryOrderTable' => AccessoryOrderTable::class,
        ] as $name => $componentClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            Livewire::test($componentClass)->assertOk();

            $count = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $count, "{$name} exceeded cold-cache <= 8 SQL query budget (got {$count}).");
        }
    }
}
