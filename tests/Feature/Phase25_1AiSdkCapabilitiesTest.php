<?php

namespace Tests\Feature;

use App\Ai\Agents\AppointmentLeadClassifierAgent;
use App\Ai\Agents\ArticleEditorialAgent;
use App\Ai\Agents\ShowroomAssistant;
use App\Ai\Agents\ShowroomRecommendationExtractor;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AppointmentTable;
use App\Mcp\Servers\BmwShowroomServer;
use App\Mcp\Tools\GetVehicleDetailsMcpTool;
use App\Mcp\Tools\SearchShowroomKnowledgeMcpTool;
use App\Mcp\Tools\SearchVehiclesMcpTool;
use App\Models\Appointment;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Laravel\Ai\Files\Base64Image;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class Phase25_1AiSdkCapabilitiesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => null,
            'ai.providers.gemini.key' => 'testing-gemini-key',
        ]);
    }

    public function test_streaming_sse_and_structured_recommendations_and_fallback(): void
    {
        $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW X5 xDrive40i',
            'slug' => 'bmw-x5-xdrive40i',
            'type' => VehicleType::CAR,
            'price' => 4159000000,
            'is_active' => true,
        ]);
        $article = Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW SAV cuối năm',
            'slug' => 'uu-dai-bmw-sav-cuoi-nam',
            'category' => Article::CATEGORY_OFFERS,
        ]);

        // Prose mentions no explicit model slug/name so ShowroomRecommendationExtractor resolves structured entities
        ShowroomAssistant::fake([
            'Mẫu SAV hạng sang cỡ trung rất phù hợp cho gia đình thường xuyên đi xa và cần khoang hành lý rộng rãi.',
        ])->preventStrayPrompts();

        ShowroomRecommendationExtractor::fake([
            [
                'recommended_product_slugs' => [$car->slug],
                'recommended_article_slugs' => [$article->slug],
                'primary_cta' => 'test_drive',
            ],
        ])->preventStrayPrompts();

        $response = $this->post(route('ai.api.assistant.stream'), [
            'message' => 'Tư vấn mẫu xe gầm cao gia đình đi xa?',
            'visitor_id' => 'visitor-stream-01',
        ]);

        $response->assertOk();
        $this->assertStringContainsString('text/event-stream', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-cache', (string) $response->headers->get('Cache-Control'));

        $sseOutput = $response->streamedContent();

        $this->assertStringContainsString("event: chunk\n", $sseOutput);
        $this->assertStringContainsString("event: done\n", $sseOutput);
        $this->assertStringContainsString('/catalog/'.$car->slug, $sseOutput);
        $this->assertStringContainsString('/tim-hieu-them/'.$article->slug, $sseOutput);
        $this->assertStringContainsString('"conversation_id":', $sseOutput);

        // Graceful fallback when stream throws exception before first chunk
        ShowroomAssistant::fake([
            fn () => throw new RuntimeException('Simulated upstream stream error'),
        ]);

        $fallbackStream = $this->post(route('ai.showroom-assistant.stream'), [
            'message' => 'Hỏi thăm xe BMW',
            'visitor_id' => 'visitor-stream-fallback',
        ]);

        $fallbackStream->assertOk();
        $fallbackContent = $fallbackStream->streamedContent();
        $this->assertStringContainsString("event: done\n", $fallbackContent);
        $this->assertStringContainsString('"status":"fallback"', $fallbackContent);
    }

    public function test_vision_image_attachment_passes_uploaded_image_to_showroom_assistant(): void
    {
        $category = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $x5 = Product::factory()->for($category)->create([
            'name' => 'BMW X5 M Sport',
            'slug' => 'bmw-x5-m-sport',
            'type' => VehicleType::CAR,
            'price' => 4299000000,
            'is_active' => true,
        ]);

        // Reject invalid non-image files
        $this->post(route('ai.api.assistant.ask'), [
            'message' => 'Nhận diện xe trong file',
            'image' => UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertStatus(422);

        ShowroomAssistant::fake([
            "Hình ảnh Quý khách gửi tương ứng với mẫu [{$x5->name}](/catalog/{$x5->slug}) đang trưng bày tại showroom.",
        ])->preventStrayPrompts();

        $response = $this->post(route('ai.api.assistant.ask'), [
            'image' => UploadedFile::fake()->image('bmw-x5.jpg', 640, 480),
            'visitor_id' => 'visitor-vision-01',
        ], ['Accept' => 'application/json']);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonFragment([
                'url' => route('products.show', $x5->slug, false),
            ]);

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            return $prompt->attachments->count() === 1
                && $prompt->attachments->first() instanceof Base64Image;
        });
    }

    public function test_admin_article_ai_assist_generates_summary_and_seo_and_blocks_non_admins(): void
    {
        $customer = User::factory()->create(['email' => 'customer@example.com']);
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);

        // Customer blocked with 403
        $this->actingAs($customer)
            ->postJson(route('admin.articles.ai-assist'), [
                'title' => 'BMW i5 thuần điện',
                'body' => 'Nội dung bài viết giới thiệu BMW i5 eDrive40 với tầm hoạt động ấn tượng.',
            ])
            ->assertForbidden();

        // AI faked response
        Config::set('showroom_ai.article_editorial_enabled', true);

        ArticleEditorialAgent::fake([
            [
                'excerpt' => 'BMW i5 eDrive40 mang lại trải nghiệm thuần điện tinh tế cùng khoang lái hiện đại.',
                'seo_title' => 'BMW i5 eDrive40 Thuần Điện Chính Hãng',
                'seo_description' => 'Khám phá BMW i5 eDrive40 mới với tầm vận hành vượt trội và ưu đãi chính hãng.',
            ],
        ])->preventStrayPrompts();

        $this->actingAs($admin)
            ->postJson(route('admin.articles.ai-assist'), [
                'title' => 'BMW i5 eDrive40 thuần điện',
                'category' => Article::CATEGORY_SHOWROOM_NEWS,
                'body' => 'BMW i5 eDrive40 mới sở hữu thiết kế thể thao thanh lịch cùng công nghệ eDrive thế hệ thứ 5.',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'ai')
            ->assertJsonPath('seo_title', 'BMW i5 eDrive40 Thuần Điện Chính Hãng')
            ->assertJsonPath('excerpt', 'BMW i5 eDrive40 mang lại trải nghiệm thuần điện tinh tế cùng khoang lái hiện đại.');

        // Deterministic fallback when AI disabled
        Config::set('showroom_ai.article_editorial_enabled', false);
        Config::set('showroom_ai.enabled', false);

        $this->actingAs($admin)
            ->postJson(route('admin.articles.ai-assist'), [
                'title' => 'Chương trình bảo dưỡng mùa mưa BMW',
                'category' => Article::CATEGORY_AFTERSALES,
                'body' => 'Kiểm tra hệ thống phanh, lốp xe và cần gạt nước miễn phí tại xưởng dịch vụ BMW chính hãng trong tháng này.',
            ])
            ->assertOk()
            ->assertJsonPath('source', 'fallback')
            ->assertJsonStructure(['excerpt', 'seo_title', 'seo_description', 'source']);
    }

    public function test_appointment_observer_classifies_lead_and_renders_badge_on_admin_table(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        Config::set('showroom_ai.lead_classification_enabled', true);

        AppointmentLeadClassifierAgent::fake([
            [
                'lead_priority' => 'hot',
                'lead_intent' => 'ready_to_buy',
                'urgent' => true,
                'readiness_score' => 94,
                'ai_lead_note' => 'Khách muốn đặt cọc BMW 330i giao ngay trong tuần.',
            ],
        ])->preventStrayPrompts();

        $hotAppointment = Appointment::factory()->guest()->create([
            'product_id' => $car->id,
            'type' => AppointmentType::Quote,
            'guest_name' => 'Pham Hot Lead',
            'guest_phone' => '0908889999',
            'appointment_date' => now()->addDay(),
            'notes' => 'Tôi cần báo giá lăn bánh để đặt cọc mua ngay tuần này.',
        ]);

        $hotAppointment->refresh();
        $this->assertSame('hot', $hotAppointment->lead_priority);
        $this->assertSame('ready_to_buy', $hotAppointment->lead_intent);
        $this->assertSame('Khách muốn đặt cọc BMW 330i giao ngay trong tuần.', $hotAppointment->ai_lead_note);

        // Rule-based fallback still classifies urgent notes even when AI disabled
        Config::set('showroom_ai.lead_classification_enabled', false);

        $serviceAppointment = Appointment::factory()->guest()->create([
            'product_id' => null,
            'type' => AppointmentType::Maintenance,
            'guest_name' => 'Nguyen Service Regular',
            'guest_phone' => '0907778888',
            'appointment_date' => now()->addDays(5),
            'notes' => 'Bảo dưỡng định kỳ 20.000km',
        ]);

        $serviceAppointment->refresh();
        $this->assertSame('normal', $serviceAppointment->lead_priority);
        $this->assertSame('aftersales_service', $serviceAppointment->lead_intent);

        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('HOT LEAD')
            ->assertSee('Khách muốn đặt cọc BMW 330i giao ngay trong tuần.')
            ->set('leadPriority', 'hot')
            ->assertSee('Pham Hot Lead')
            ->assertDontSee('Nguyen Service Regular');
    }

    public function test_mcp_server_and_tools_return_verified_catalog_details_and_rag_knowledge(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $activeCar = Product::factory()->for($category)->create([
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 3289000000,
            'deposit_amount' => 100000000,
            'specifications' => ['engine' => '2.0L TwinPower Turbo', 'power' => '252 HP'],
            'is_active' => true,
        ]);

        $hiddenCar = Product::factory()->for($category)->create([
            'name' => 'BMW Secret Prototype',
            'slug' => 'bmw-secret-prototype',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        // 1. SearchVehiclesMcpTool
        BmwShowroomServer::tool(SearchVehiclesMcpTool::class, [
            'keyword' => '530i',
            'type' => 'car',
        ])
            ->assertOk()
            ->assertSee('BMW 530i M Sport')
            ->assertDontSee('BMW Secret Prototype');

        // 2. GetVehicleDetailsMcpTool (active vs inactive)
        BmwShowroomServer::tool(GetVehicleDetailsMcpTool::class, [
            'slug' => $activeCar->slug,
        ])
            ->assertOk()
            ->assertSee('BMW 530i M Sport')
            ->assertSee('2.0L TwinPower Turbo');

        BmwShowroomServer::tool(GetVehicleDetailsMcpTool::class, [
            'slug' => $hiddenCar->slug,
        ])->assertHasErrors();

        // 3. SearchShowroomKnowledgeMcpTool
        BmwShowroomServer::tool(SearchShowroomKnowledgeMcpTool::class, [
            'query' => 'chính sách bảo hành xe điện BMW',
            'limit' => 3,
        ])
            ->assertOk()
            ->assertSee('chunks');

        // 4. Web MCP route lists registered tools cleanly
        $this->postJson('/mcp/showroom', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ])
            ->assertOk()
            ->assertSee('search-vehicles-mcp-tool')
            ->assertSee('get-vehicle-details-mcp-tool')
            ->assertSee('search-showroom-knowledge-mcp-tool');
    }
}
