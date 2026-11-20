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
use App\Mail\Admin\ShowroomDailySummaryMail;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Mail\Customer\AppointmentConfirmationMail;
use App\Mail\Customer\GuestAppointmentChangeMail;
use App\Mail\CustomerPromotionMail;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class Phase30UiRedesignAndCleanEmailsTest extends TestCase
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

    private function createCatalogFixtures(): array
    {
        $category = Category::factory()->create([
            'name' => 'BMW Sedan Series',
            'slug' => 'bmw-sedan-series-'.uniqid(),
        ]);

        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport-'.uniqid(),
            'type' => VehicleType::CAR,
            'price' => 2759000000,
            'deposit_amount' => 100000000,
            'stock' => 6,
            'is_active' => true,
        ]);

        $accessory = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Thảm Lót Sàn BMW M Performance',
            'slug' => 'tham-lot-san-bmw-m-'.uniqid(),
            'type' => VehicleType::ACCESSORY,
            'price' => 4500000,
            'deposit_amount' => 0,
            'stock' => 15,
            'is_active' => true,
        ]);

        return [$category, $car, $accessory];
    }

    private function assertCleanLuxuryEmailHtml(string $html): void
    {
        $this->assertStringContainsString('#F1F5F9', $html);
        $this->assertStringContainsString('#FFFFFF', $html);
        $this->assertStringContainsString('border-radius: 16px', $html);
        $this->assertStringContainsString('-apple-system', $html);

        $this->assertStringNotContainsString('Courier', $html);
        $this->assertStringNotContainsString('Consolas', $html);
        $this->assertStringNotContainsString('Monaco', $html);
        $this->assertStringNotContainsString('monospace', strtolower($html));
    }

    public function test_all_five_appointment_preview_modes_and_nine_email_templates_render_clean_light_minimalist_luxury(): void
    {
        $admin = $this->createAdmin();
        [, $car, $accessory] = $this->createCatalogFixtures();

        $appointment = Appointment::create([
            'guest_name' => 'Trần Minh Khôi',
            'guest_phone' => '0909123456',
            'guest_email' => 'khoi.tran@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2)->setTime(10, 0),
            'status' => AppointmentStatus::Confirmed,
            'lead_priority' => Appointment::LEAD_PRIORITY_HOT,
            'lead_intent' => 'book_test_drive',
            'notes' => 'Chuẩn bị sẵn bản màu Đen Sapphire.',
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Lê Bảo Ngọc',
            'customer_phone' => '0912333444',
            'customer_email' => 'ngoc.le@example.com',
            'customer_address' => '12 Lê Lợi, Quận 1, TP.HCM',
            'quantity' => 2,
            'unit_price' => 4500000,
            'total_amount' => 9000000,
            'status' => AccessoryOrder::STATUS_CONFIRMED,
            'notes' => 'Giao giờ hành chính.',
        ]);

        $previewModes = [
            AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED => '#1C69D4',
            AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED => '#059669',
            'guest_rescheduled' => '#D97706',
            'reminder' => '#4F46E5',
            'followup' => '#B45309',
        ];

        foreach ($previewModes as $mode => $accentColor) {
            $response = $this->actingAs($admin)->get(route('admin.mail-preview.appointment', [
                'appointment' => $appointment->id,
                'mode' => $mode,
            ]));

            $response->assertOk();
            $html = $response->getContent();
            $this->assertCleanLuxuryEmailHtml($html);
            $this->assertStringContainsString($accentColor, $html);
        }

        // Verify X-BMW-Mail-Mode header + plain-text variant on system vs confirmed mail
        $systemMail = new AppointmentConfirmationMail(
            $appointment,
            AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED,
        );
        $this->assertSame(AppointmentConfirmationMail::MODE_SYSTEM_RECEIVED, $systemMail->headers()->text['X-BMW-Mail-Mode']);
        $this->assertNotEmpty($systemMail->content()->text);

        $confirmedMail = new AppointmentConfirmationMail(
            $appointment,
            AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED,
            $admin->name,
            $admin->roleLabel(),
        );
        $this->assertSame(AppointmentConfirmationMail::MODE_ADMIN_CONFIRMED, $confirmedMail->headers()->text['X-BMW-Mail-Mode']);
        $this->assertNotEmpty($confirmedMail->content()->text);

        $mailables = [
            new GuestAppointmentChangeMail(
                $appointment,
                GuestAppointmentChangeMail::ACTION_RESCHEDULE,
                '09:00 — 20/11/2026',
                '14:00 — 22/11/2026',
                'Chờ xác nhận',
                'Đã xác nhận',
                'Đổi lịch công tác.',
            ),
            new HotLeadAlertMail($appointment),
            new AppointmentAlertMail($appointment),
            new AccessoryOrderAlertMail($order),
            new AccessoryOrderConfirmationMail($order),
            new AccessoryOrderStatusMail($order),
            new ShowroomDailySummaryMail([
                'date_label' => now()->format('d/m/Y'),
                'total_appointments' => 12,
                'hot_leads' => 4,
                'service_bookings' => 5,
                'confirmed_or_completed' => 9,
                'accessory_orders_count' => 3,
                'accessory_orders_value' => 27000000,
            ]),
            new CustomerPromotionMail(
                customerName: 'Trần Minh Khôi',
                vehicleName: 'BMW 530i M Sport',
                offerTitle: 'Đặc quyền bảo dưỡng mùa lễ hội',
                offerContent: "Miễn phí kiểm tra 20 hạng mục.\nƯu đãi 15% phụ kiện chính hãng.",
            ),
        ];

        /** @var Mailable $mailable */
        foreach ($mailables as $mailable) {
            $renderedHtml = $mailable->render();
            $this->assertCleanLuxuryEmailHtml($renderedHtml);

            $textView = $mailable->content()->text;
            if (is_string($textView) && $textView !== '') {
                $this->assertTrue(view()->exists($textView), "Missing plain-text view [{$textView}].");
            }
        }
    }

    public function test_admin_cockpit_pages_render_soft_rounded_theme_and_ai_metadata_pills_while_public_preserves_rounded_none(): void
    {
        $admin = $this->createAdmin();
        [$category, $car, $accessory] = $this->createCatalogFixtures();

        $serviceBooking = Appointment::create([
            'guest_name' => 'Phạm Gia Huy',
            'guest_phone' => '0988777666',
            'guest_email' => 'huy.pham@example.com',
            'type' => AppointmentType::Maintenance,
            'service_package' => 'maintenance_major',
            'appointment_date' => now()->addDay()->setTime(8, 30),
            'status' => AppointmentStatus::Pending,
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Vũ Hải Nam',
            'customer_phone' => '0977111222',
            'customer_email' => 'nam.vu@example.com',
            'customer_address' => '88 Hai Bà Trưng, Hà Nội',
            'quantity' => 1,
            'unit_price' => 4500000,
            'total_amount' => 4500000,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        $article = Article::create([
            'user_id' => $admin->id,
            'title' => 'Trải nghiệm BMW 530i M Sport mới',
            'slug' => 'trai-nghiem-bmw-530i-m-sport-'.uniqid(),
            'category' => Article::CATEGORY_BMW_EXPERIENCE,
            'excerpt' => 'Tóm tắt trải nghiệm vận hành BMW 530i M Sport.',
            'body' => "## Tổng quan\n\nBMW 530i M Sport mang lại cảm giác lái chuẩn xác.",
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);

        $aiSession = AiChatSession::create([
            'visitor_id' => 'vis-phase30-01',
            'ip_address' => '127.0.0.1',
            'display_name' => 'Khách AI Phase 30',
            'last_intent' => 'compare_models',
            'context_product_id' => $car->id,
            'context_product_name' => $car->name,
            'status' => AiChatSession::STATUS_INTERESTED,
            'message_count' => 2,
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now(),
        ]);

        AiChatMessage::create([
            'ai_chat_session_id' => $aiSession->id,
            'role' => 'assistant',
            'content' => 'Tôi gợi ý mẫu BMW 530i M Sport phù hợp với nhu cầu của anh/chị.',
            'response_reason' => 'ok',
            'meta_data' => [
                'intent' => 'compare_models',
                'lead_level' => 'hot',
                'recommended_product_ids' => [$car->id],
                'recommended_service_package' => 'maintenance_major',
                'tool_calls' => ['SearchShowroomCatalogTool'],
            ],
        ]);

        $adminRoutes = [
            route('dashboard'),
            route('admin.appointments.index'),
            route('admin.service-bookings.index'),
            route('admin.accessory-orders.index'),
            route('admin.accessory-orders.show', $order),
            route('admin.ai-conversations.index'),
            route('admin.ai-conversations.show', $aiSession),
            route('admin.customers.index'),
            route('admin.users.index'),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $car),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.articles.index'),
            route('admin.articles.create'),
            route('admin.articles.edit', $article),
            route('admin.site-settings.edit'),
        ];

        foreach ($adminRoutes as $url) {
            $res = $this->actingAs($admin)->get($url);
            $res->assertOk();
            $res->assertSee('admin-cockpit', false);

            // Shared UI select component uses CSS `.admin-cockpit [data-custom-select-*]` border-radius overrides
            // so Public keeps `rounded-none` while Admin renders 12px rounded corners.
            $withoutSharedSelect = str_replace([
                'rounded-none border border-[#242B38] bg-[#0B0F17]',
                'rounded-none border border-[#242B38] bg-[#0E131D]',
            ], '', $res->getContent());
            $this->assertStringNotContainsString('rounded-none', $withoutSharedSelect, "Unexpected rounded-none on {$url}");
        }

        $appCss = file_get_contents(resource_path('css/app.css')) ?: '';
        $this->assertStringContainsString('.admin-cockpit [data-custom-select-trigger]', $appCss);
        $this->assertStringContainsString('.admin-cockpit [data-bmw-pagination="bar"]', $appCss);

        // Verify AI Conversation detail uses rounded-full pills for Phase 29 metadata
        $aiDetailRes = $this->actingAs($admin)->get(route('admin.ai-conversations.show', $aiSession));
        $aiDetailRes->assertOk();
        $aiDetailRes->assertSee('INTENT: compare_models');
        $aiDetailRes->assertSee('LEAD: HOT');
        $aiDetailRes->assertSee('TOOL: SearchShowroomCatalogTool');
        $aiDetailRes->assertSee('rounded-full border border-[#1C69D4]/40', false);
        $aiDetailRes->assertSee('rounded-full border border-violet-500/35', false);

        // Verify Public site renders unified soft-rounded geometry without rounded-none or admin-cockpit
        foreach ([route('products.index'), route('articles.index'), route('appointments.create')] as $publicUrl) {
            $pub = $this->get($publicUrl);
            $pub->assertOk();
            $pub->assertDontSee('class="admin-cockpit', false);
            $pub->assertDontSee('rounded-none', false);
            $pub->assertSee('rounded-xl', false);
        }

        $this->assertNotNull($serviceBooking->id);
    }

    public function test_three_crm_tables_and_drawers_stay_within_8_sql_queries_on_cold_cache(): void
    {
        $admin = $this->createAdmin();
        [, $car, $accessory] = $this->createCatalogFixtures();

        $testDrive = Appointment::create([
            'guest_name' => 'Nguyễn Tuấn Kiệt',
            'guest_phone' => '0901222333',
            'guest_email' => 'kiet.nguyen@example.com',
            'product_id' => $car->id,
            'type' => AppointmentType::TestDrive,
            'appointment_date' => now()->addDays(2),
            'status' => AppointmentStatus::Pending,
        ]);

        $serviceBooking = Appointment::create([
            'guest_name' => 'Đặng Thu Hà',
            'guest_phone' => '0902333444',
            'guest_email' => 'ha.dang@example.com',
            'type' => AppointmentType::Maintenance,
            'service_package' => 'oil_change',
            'appointment_date' => now()->addDays(3),
            'status' => AppointmentStatus::Pending,
        ]);

        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Hoàng Đức Anh',
            'customer_phone' => '0903444555',
            'customer_email' => 'anh.hoang@example.com',
            'customer_address' => '15 Nguyễn Huệ, TP.HCM',
            'quantity' => 1,
            'unit_price' => 4500000,
            'total_amount' => 4500000,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);

        foreach ([
            route('admin.appointments.index') => AppointmentTable::class,
            route('admin.service-bookings.index') => ServiceBookingTable::class,
            route('admin.accessory-orders.index') => AccessoryOrderTable::class,
        ] as $routeUrl => $componentClass) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->actingAs($admin)->get($routeUrl)->assertOk();
            $routeQueries = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertLessThanOrEqual(8, $routeQueries, "{$componentClass} cold page load used {$routeQueries} queries.");
        }

        $appointmentHtml = Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('rounded-2xl')
            ->call('inspectAppointment', $testDrive->id)
            ->assertSee('rounded-l-2xl')
            ->html();
        $this->assertStringNotContainsString('rounded-none', str_replace([
            'rounded-none border border-[#242B38] bg-[#0B0F17]',
            'rounded-none border border-[#242B38] bg-[#0E131D]',
        ], '', $appointmentHtml));

        $serviceHtml = Livewire::actingAs($admin)
            ->test(ServiceBookingTable::class)
            ->assertSee('rounded-2xl')
            ->call('inspectBooking', $serviceBooking->id)
            ->assertSee('rounded-l-2xl')
            ->html();
        $this->assertStringNotContainsString('rounded-none', str_replace([
            'rounded-none border border-[#242B38] bg-[#0B0F17]',
            'rounded-none border border-[#242B38] bg-[#0E131D]',
        ], '', $serviceHtml));

        $orderHtml = Livewire::actingAs($admin)
            ->test(AccessoryOrderTable::class)
            ->assertSee('rounded-2xl')
            ->call('inspectOrder', $order->id)
            ->assertSee('rounded-l-2xl')
            ->html();
        $this->assertStringNotContainsString('rounded-none', str_replace([
            'rounded-none border border-[#242B38] bg-[#0B0F17]',
            'rounded-none border border-[#242B38] bg-[#0E131D]',
        ], '', $orderHtml));
    }
}
