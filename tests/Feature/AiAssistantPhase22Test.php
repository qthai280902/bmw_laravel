<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Ai\Tools\CompareVehiclesTool;
use App\Ai\Tools\SearchArticlesAndOffersTool;
use App\Ai\Tools\SearchCatalogTool;
use App\Ai\Tools\SearchKnowledgeBaseTool;
use App\Enums\VehicleType;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Ai\ShowroomAssistantService;
use App\Services\Ai\ShowroomCatalogMatcher;
use App\Services\Ai\ShowroomKnowledgeBaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\ObjectSchema;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Tools\Request as ToolRequest;
use Tests\TestCase;

class AiAssistantPhase22Test extends TestCase
{
    use RefreshDatabase;

    public function test_search_catalog_tool_filters_active_products_and_supports_vietnamese_synonyms(): void
    {
        $savCategory = Category::factory()->create(['name' => 'SAV', 'slug' => 'sav']);
        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $motorradCategory = Category::factory()->create(['name' => 'Sport', 'slug' => 'sport']);
        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện', 'slug' => 'phu-kien']);

        $x5 = Product::factory()->create([
            'category_id' => $savCategory->id,
            'name' => 'BMW X5 xDrive40i',
            'slug' => 'bmw-x5-xdrive40i',
            'type' => VehicleType::CAR,
            'price' => 3350000000,
            'is_active' => true,
        ]);

        $i4 = Product::factory()->create([
            'category_id' => $sedanCategory->id,
            'name' => 'BMW i4 eDrive40',
            'slug' => 'bmw-i4-edrive40',
            'type' => VehicleType::CAR,
            'price' => 2750000000,
            'is_active' => true,
        ]);

        $s1000rr = Product::factory()->create([
            'category_id' => $motorradCategory->id,
            'name' => 'BMW S1000RR',
            'slug' => 'bmw-s1000rr',
            'type' => VehicleType::MOTORBIKE,
            'price' => 949000000,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $accessoryCategory->id,
            'name' => 'Thảm lót sàn BMW chính hãng',
            'slug' => 'tham-lot-san-bmw',
            'type' => VehicleType::ACCESSORY,
            'price' => 6500000,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $savCategory->id,
            'name' => 'BMW X7 Secret Hidden',
            'slug' => 'bmw-x7-secret-hidden',
            'type' => VehicleType::CAR,
            'price' => 5500000000,
            'is_active' => false,
        ]);

        $tool = app(SearchCatalogTool::class);

        $suvResult = json_decode((string) $tool->handle(new ToolRequest(['keyword' => 'SUV'])), true);
        $this->assertSame('ok', $suvResult['status']);
        $this->assertSame([$x5->slug], array_column($suvResult['items'], 'slug'));
        $this->assertStringContainsString('/catalog/'.$x5->slug, $suvResult['items'][0]['url']);
        $this->assertStringContainsString('/booking?type=test_drive', $suvResult['items'][0]['booking_test_drive_url']);

        $evResult = json_decode((string) $tool->handle(new ToolRequest(['keyword' => 'xe điện'])), true);
        $this->assertSame('ok', $evResult['status']);
        $this->assertSame([$i4->slug], array_column($evResult['items'], 'slug'));

        $motoResult = json_decode((string) $tool->handle(new ToolRequest(['keyword' => 'xe máy pkl'])), true);
        $this->assertSame('ok', $motoResult['status']);
        $this->assertSame([$s1000rr->slug], array_column($motoResult['items'], 'slug'));

        $budgetResult = json_decode((string) $tool->handle(new ToolRequest([
            'type' => 'car',
            'max_price' => 3000000000,
        ])), true);
        $this->assertSame([$i4->slug], array_column($budgetResult['items'], 'slug'));

        $accessoryResult = json_decode((string) $tool->handle(new ToolRequest(['type' => 'accessory'])), true);
        $this->assertSame(['tham-lot-san-bmw'], array_column($accessoryResult['items'], 'slug'));
        $this->assertStringContainsString('/accessories/tham-lot-san-bmw/order', $accessoryResult['items'][0]['accessory_order_url']);
        $this->assertNull($accessoryResult['items'][0]['booking_test_drive_url']);

        $hiddenCheck = json_decode((string) $tool->handle(new ToolRequest(['keyword' => 'BMW X7 Secret'])), true);
        $this->assertSame('no_match', $hiddenCheck['status']);
        $this->assertSame(0, $hiddenCheck['total_found']);
    }

    public function test_compare_vehicles_tool_compares_active_vehicles_deduplicates_matches_and_excludes_hidden_or_accessories(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);

        $car330i = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 330i Sedan',
            'slug' => 'bmw-330i-sedan',
            'type' => VehicleType::CAR,
            'price' => 1799000000,
            'is_active' => true,
        ]);

        $car530i = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 530i M Sport',
            'slug' => 'bmw-530i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2799000000,
            'is_active' => true,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW Hidden Concept',
            'slug' => 'bmw-hidden-concept',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW 330i M Floor Mat',
            'slug' => 'bmw-330i-m-floor-mat',
            'type' => VehicleType::ACCESSORY,
            'is_active' => true,
        ]);

        $tool = app(CompareVehiclesTool::class);

        $compareResult = json_decode((string) $tool->handle(new ToolRequest([
            'vehicles' => ['BMW 330i Sedan', 'BMW 530i', 'BMW Hidden Concept'],
        ])), true);

        $this->assertSame('ok', $compareResult['status']);
        $this->assertCount(2, $compareResult['matched_vehicles']);
        $this->assertSame(
            "/compare?ids={$car330i->id}%2C{$car530i->id}",
            str_replace(',', '%2C', (string) $compareResult['compare_url']) === "/compare?ids={$car330i->id}%2C{$car530i->id}"
                ? str_replace(',', '%2C', (string) $compareResult['compare_url'])
                : $compareResult['compare_url']
        );
        $this->assertStringContainsString('/compare?ids=', (string) $compareResult['compare_url']);
        $this->assertContains('BMW Hidden Concept', $compareResult['unmatched_queries']);

        // Deduplication check: aliasing the same car twice yields a single unique vehicle, clear guidance,
        // and auto-suggests the closest active same-segment companion for immediate comparison.
        $duplicateResult = json_decode((string) $tool->handle(new ToolRequest([
            'vehicles' => ['BMW 330i', 'bmw-330i-sedan'],
        ])), true);

        $this->assertSame('needs_more_vehicles', $duplicateResult['status']);
        $this->assertCount(1, $duplicateResult['matched_vehicles']);
        $this->assertNull($duplicateResult['compare_url']);
        $this->assertStringContainsString('Chỉ khớp 1 mẫu xe duy nhất sau khi lọc trùng', (string) $duplicateResult['message']);
        $this->assertNotEmpty($duplicateResult['available_vehicle_suggestions']);
        $this->assertSame($car530i->slug, $duplicateResult['suggested_comparison_vehicle']['slug']);
        $this->assertStringContainsString('/compare?ids=', (string) $duplicateResult['suggested_compare_url']);
        $this->assertStringContainsString((string) $car530i->id, (string) $duplicateResult['suggested_compare_url']);
    }

    public function test_search_articles_and_offers_tool_returns_only_published_articles(): void
    {
        $published = Article::factory()->published()->create([
            'title' => 'Ưu đãi đặc quyền BMW Mùa Hè 2026',
            'slug' => 'uu-dai-bmw-mua-he-2026',
            'category' => 'offers',
            'excerpt' => 'Tặng gói bảo dưỡng chính hãng BMW Service.',
        ]);

        Article::factory()->create([
            'title' => 'Bài nháp ưu đãi nội bộ chưa công bố',
            'slug' => 'bai-nhap-uu-dai-noi-bo',
            'category' => 'offers',
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ]);

        Article::factory()->create([
            'title' => 'Ưu đãi hẹn giờ tương lai bí mật',
            'slug' => 'uu-dai-hen-gio-tuong-lai',
            'category' => 'offers',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->addDays(5),
        ]);

        $tool = app(SearchArticlesAndOffersTool::class);
        $result = json_decode((string) $tool->handle(new ToolRequest([
            'keyword' => 'ưu đãi',
            'category' => 'offers',
        ])), true);

        $this->assertSame('ok', $result['status']);
        $this->assertCount(1, $result['articles']);
        $this->assertSame($published->slug, $result['articles'][0]['slug']);
        $this->assertStringContainsString('/tim-hieu-them/'.$published->slug, $result['articles'][0]['url']);
    }

    public function test_agent_registers_all_four_tools_with_valid_schemas_and_real_routes(): void
    {
        $agent = new ShowroomAssistant;
        $tools = collect($agent->tools());

        $this->assertCount(4, $tools);
        $this->assertSame(
            ['search_catalog', 'compare_vehicles', 'search_articles_and_offers', 'search_knowledge_base'],
            $tools->map(fn ($tool): string => $tool->name())->values()->all()
        );

        $factory = new JsonSchemaTypeFactory;

        foreach ($tools as $tool) {
            $schema = new ObjectSchema($tool->schema($factory));
            $arraySchema = $schema->toArray();
            $this->assertSame('object', $arraySchema['type']);
        }

        $instructions = (string) $agent->instructions();
        $this->assertStringContainsString('BMW showroom gồm ô tô BMW, BMW Motorrad, phụ kiện', $instructions);
        $this->assertStringContainsString('/booking?type=test_drive', $instructions);
        $this->assertStringContainsString('/booking?type=quote', $instructions);
        $this->assertStringContainsString('/accessories', $instructions);
        $this->assertStringNotContainsString('/dat-lich', $instructions);
        $this->assertStringNotContainsString('/phu-kien', $instructions);
    }

    public function test_conversational_memory_hydrates_turns_and_reset_clears_ai_context_while_preserving_crm_logs(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'AIzaPhase22FakeKey1234567890',
        ]);

        Product::factory()->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);

        ShowroomAssistant::fake([
            'Mẫu BMW 330i M Sport hiện có giá niêm yết tại showroom.',
            'BMW 330i M Sport được bảo hành chính hãng 3 năm.',
            'Xin chào Quý khách, BMW Showroom rất hân hạnh được hỗ trợ.',
        ]);

        $visitorId = 'phase22-visitor-memory-01';

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tư vấn cho tôi BMW 330i M Sport',
            'visitor_id' => $visitorId,
        ])->assertOk();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Xe này bảo hành bao lâu?',
            'visitor_id' => $visitorId,
        ])->assertOk();

        // Reset memory via endpoint.
        $this->postJson(route('ai.showroom-assistant.reset'), [
            'visitor_id' => $visitorId,
        ])->assertOk()->assertJson([
            'status' => 'ok',
            'reset' => true,
        ]);

        // Empty or stale reset payloads also succeed gracefully without blocking UI.
        $this->postJson(route('ai.showroom-assistant.reset'), [])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'reset' => true]);

        $this->postJson(route('ai.showroom-assistant.reset'), ['visitor_id' => 'invalid id with spaces!!!'])
            ->assertOk()
            ->assertJson(['status' => 'ok', 'reset' => true]);

        // Turn 3 in the same wall-clock second starts with empty conversational history.
        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Showroom hiện có chương trình gì?',
            'visitor_id' => $visitorId,
        ])->assertOk();

        ShowroomAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => Str::contains($prompt->prompt, 'Tư vấn cho tôi BMW 330i M Sport')
            && collect($prompt->agent->messages())->isEmpty());

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            if (! Str::contains($prompt->prompt, 'Xe này bảo hành bao lâu?')) {
                return false;
            }

            $messages = collect($prompt->agent->messages())->values();

            return $messages->count() === 2
                && $messages[0] instanceof Message
                && $messages[0]->role === MessageRole::User
                && Str::contains((string) $messages[0]->content, 'BMW 330i M Sport')
                && $messages[1] instanceof Message
                && $messages[1]->role === MessageRole::Assistant;
        });

        ShowroomAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => Str::contains($prompt->prompt, 'Showroom hiện có chương trình gì?')
            && collect($prompt->agent->messages())->isEmpty());

        // CRM database messages remain 100% intact for Admin follow-up (3 user + 3 assistant).
        $session = AiChatSession::query()->where('visitor_id', $visitorId)->firstOrFail();
        $this->assertSame(6, AiChatMessage::query()->where('ai_chat_session_id', $session->id)->count());
    }

    public function test_page_context_awareness_injects_active_product_and_syncs_into_admin_crm_without_leaking_inactive(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'AIzaPhase22FakeKey1234567890',
        ]);

        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);

        $activeCar = Product::factory()->create([
            'category_id' => $sedanCategory->id,
            'name' => 'BMW 530i Executive',
            'slug' => 'bmw-530i-executive',
            'type' => VehicleType::CAR,
            'price' => 2890000000,
            'is_active' => true,
        ]);

        $hiddenCar = Product::factory()->create([
            'category_id' => $sedanCategory->id,
            'name' => 'BMW Hidden Prototype',
            'slug' => 'bmw-hidden-prototype',
            'type' => VehicleType::CAR,
            'is_active' => false,
        ]);

        ShowroomAssistant::fake([
            'BMW 530i Executive đang có sẵn lịch lái thử tại showroom.',
            'BMW Showroom sẵn sàng hỗ trợ Quý khách.',
        ]);

        $response = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Chiếc đang xem bảo hành bao lâu?',
            'visitor_id' => 'phase22-crm-visitor-01',
            'page_url' => url('/catalog/'.$activeCar->slug),
            'product_slug' => $activeCar->slug,
            'product_name' => $activeCar->name,
        ])->assertOk();

        $this->assertStringContainsString('/booking?type=test_drive', json_encode($response->json('suggestions')));
        $this->assertArrayNotHasKey('current_product', $response->json());

        // Hidden product slug must never leak into prompt or CRM session.
        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Chiếc này giá sao?',
            'visitor_id' => 'phase22-hidden-visitor-02',
            'page_url' => url('/catalog/'.$hiddenCar->slug),
            'product_slug' => $hiddenCar->slug,
        ])->assertOk();

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt) use ($activeCar, $hiddenCar): bool {
            if (Str::contains($prompt->prompt, 'Chiếc đang xem bảo hành bao lâu')) {
                return Str::contains($prompt->prompt, 'Ngữ cảnh trang hiện tại')
                    && Str::contains($prompt->prompt, $activeCar->name);
            }

            return ! Str::contains($prompt->prompt, $hiddenCar->name);
        });

        $crmSession = AiChatSession::query()
            ->where('visitor_id', 'phase22-crm-visitor-01')
            ->firstOrFail();

        $this->assertSame($activeCar->id, $crmSession->context_product_id);
        $this->assertSame($activeCar->name, $crmSession->context_product_name);
        $this->assertSame($activeCar->slug, $crmSession->context_product_slug);

        $hiddenSession = AiChatSession::query()
            ->where('visitor_id', 'phase22-hidden-visitor-02')
            ->firstOrFail();

        $this->assertNull($hiddenSession->context_product_id);
        $this->assertNull($hiddenSession->context_product_name);

        // Verify Admin CRM UI renders the viewed product clearly.
        $admin = User::factory()->create(['email' => 'crm@bmw.com']);

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.index'))
            ->assertOk()
            ->assertSee('Đang xem: '.$activeCar->name);

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.show', $crmSession))
            ->assertOk()
            ->assertSee('Sản phẩm đang xem')
            ->assertSee($activeCar->name);
    }

    public function test_product_detail_and_widget_ui_enforce_phase22_ux_zero_radius_and_xss_safety(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $accessoryCategory = Category::factory()->create(['name' => 'Phụ kiện', 'slug' => 'phu-kien']);
        $car = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'BMW M340i xDrive',
            'slug' => 'bmw-m340i-xdrive',
            'type' => VehicleType::CAR,
            'is_active' => true,
        ]);
        $accessory = Product::factory()->create([
            'category_id' => $accessoryCategory->id,
            'name' => 'Ốp gương Carbon BMW M',
            'slug' => 'op-guong-carbon-bmw-m',
            'type' => VehicleType::ACCESSORY,
            'is_active' => true,
        ]);

        $carHtml = $this->get(route('products.show', $car->slug))->assertOk()->getContent();
        $this->assertStringContainsString('data-ai-page-context', $carHtml);
        $this->assertStringContainsString('data-ai-context-trigger', $carHtml);
        $this->assertStringContainsString('Hỏi trợ lý AI về '.$car->name, $carHtml);
        $this->assertStringContainsString('bmw-ai:ask', $carHtml);

        // Accessory page keeps strict isolation from vehicle test-drive / vehicle quote query strings.
        $accHtml = $this->get(route('products.show', $accessory->slug))->assertOk()->getContent();
        $this->assertStringContainsString('Hỏi trợ lý AI về '.$accessory->name, $accHtml);
        $this->assertStringNotContainsString('Đăng ký lái thử', $accHtml);
        $this->assertStringNotContainsString('type=test_drive', $accHtml);
        $this->assertStringNotContainsString('product_id=', $accHtml);

        $widgetBlade = file_get_contents(resource_path('views/components/public/ai-assistant-widget.blade.php')) ?: '';
        $this->assertStringContainsString('resetEndpoint', $widgetBlade);
        $this->assertStringContainsString('@keydown.escape.window', $widgetBlade);
        $this->assertStringContainsString('data-ai-context-badge', $widgetBlade);
        $this->assertStringContainsString('data-ai-action-chip', $widgetBlade);
        $this->assertStringContainsString('visiblePartText(part, message.revealedChars).length > 0', $widgetBlade);
        $this->assertStringNotContainsString('x-html', $widgetBlade);
        $this->assertStringNotContainsString('rounded-', $widgetBlade);

        $appJs = file_get_contents(resource_path('js/app.js')) ?: '';
        $this->assertStringContainsString('detectPageContext()', $appJs);
        $this->assertStringContainsString('handleContextualAsk(', $appJs);
        $this->assertStringContainsString("window.addEventListener('bmw-ai:ask'", $appJs);
        $this->assertStringContainsString("this.config.resetEndpoint || '/ai/showroom-assistant/reset'", $appJs);
        $this->assertStringContainsString('actionMeta(', $appJs);
        $this->assertStringContainsString('visiblePartText(', $appJs);

        $appCss = file_get_contents(resource_path('css/app.css')) ?: '';
        $this->assertMatchesRegularExpression('/\.ai-message-bubble\s*\{[^}]*border-radius:\s*0(?:px)?\s*;/s', $appCss);
        $this->assertMatchesRegularExpression('/\.ai-assistant-launcher\s*\{[^}]*border-radius:\s*0(?:px)?\s*;/s', $appCss);
        $this->assertMatchesRegularExpression('/\[data-ai-action-chip\]\s*\{[^}]*border-radius:\s*0(?:px)?\s*;/s', $appCss);
    }

    public function test_search_knowledge_base_tool_and_rag_prompt_grounding_and_resilience(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'AIzaPhase221RagKey1234567890',
        ]);

        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $car = Product::factory()->create([
            'category_id' => $sedanCategory->id,
            'name' => 'BMW i4 eDrive40',
            'slug' => 'bmw-i4-edrive40',
            'type' => VehicleType::CAR,
            'price' => 2750000000,
            'is_active' => true,
        ]);

        Article::factory()->published()->create([
            'title' => 'Ưu đãi bảo dưỡng CBS mùa thu',
            'slug' => 'uu-dai-bao-duong-cbs-mua-thu',
            'category' => 'service',
            'excerpt' => 'Miễn phí kiểm tra xe đa điểm theo tiêu chuẩn BMW CBS.',
        ]);

        $matcher = app(ShowroomCatalogMatcher::class);
        $knowledgeBase = app(ShowroomKnowledgeBaseService::class);
        $chunks = $knowledgeBase->knowledgeChunks();
        $this->assertCount(7, $chunks);

        // Resilience guard: missing or corrupted JSON files never raise exceptions.
        $missingService = new ShowroomKnowledgeBaseService($matcher, storage_path('framework/testing/non-existent-rag.json'));
        $this->assertSame([], $missingService->knowledgeChunks());

        $badJsonPath = storage_path('framework/testing/corrupt-rag.json');
        File::ensureDirectoryExists(dirname($badJsonPath));
        File::put($badJsonPath, '{invalid json');
        try {
            $corruptService = new ShowroomKnowledgeBaseService($matcher, $badJsonPath);
            $this->assertSame([], $corruptService->knowledgeChunks());
        } finally {
            File::delete($badJsonPath);
        }

        // Tool hybrid search retrieves both RAG warranty policy and matching active EV product.
        $tool = app(SearchKnowledgeBaseTool::class);
        $result = json_decode((string) $tool->handle(new ToolRequest([
            'query' => 'Chính sách bảo hành pin xe điện BMW i4',
        ])), true);

        $this->assertSame('ok', $result['status']);
        $this->assertGreaterThan(0, $result['total_found']);
        $ids = array_column($result['chunks'], 'id');
        $this->assertContains('warranty_policy', $ids);
        $this->assertContains('product:'.$car->slug, $ids);

        // Endpoint automatically injects verified RAG section into the generated prompt.
        ShowroomAssistant::fake(['Pin xe thuần điện BMW i được bảo hành chính hãng 8 năm hoặc 160.000 km.']);

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Chính sách bảo hành pin xe điện và trả góp như thế nào?',
            'visitor_id' => 'phase221-rag-visitor-01',
        ])->assertOk();

        ShowroomAssistant::assertPrompted(fn (AgentPrompt $prompt): bool => Str::contains($prompt->prompt, '[TÀI LIỆU TRI THỨC RAG ĐƯỢC XÁC THỰC]')
            && Str::contains($prompt->prompt, 'warranty_policy')
            && Str::contains($prompt->prompt, 'Dữ liệu public của showroom BMW'));
    }

    public function test_markdown_link_sanitizer_repairs_empty_hash_bad_slug_and_legacy_routes_without_touching_plain_text(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.gemini_keys.primary' => 'AIzaPhase221Sanitizer12345678',
        ]);

        $sedanCategory = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        Product::factory()->create([
            'category_id' => $sedanCategory->id,
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 1799000000,
            'is_active' => true,
        ]);

        Article::factory()->published()->create([
            'title' => 'Ưu đãi BMW mùa thu',
            'slug' => 'uu-dai-bmw-mua-thu',
            'category' => 'offers',
            'excerpt' => 'Thông tin ưu đãi chính thức từ showroom BMW.',
        ]);

        $service = app(ShowroomAssistantService::class);
        $plainText = 'BMW 330i phù hợp nếu bạn muốn sedan thể thao.';
        $this->assertSame($plainText, $service->sanitizeAndRepairMarkdownLinks($plainText));

        $dirtyReply = implode("\n", [
            '- Xe đề xuất: [BMW 330i M Sport]().',
            '- Sai slug xe: [BMW 330i M Sport](/catalog/bmw-330i-fake-slug).',
            '- Sai slug bài viết: [Ưu đãi BMW mùa thu](/tim-hieu-them/bai-ao-khong-co).',
            '- Hash link: [Đăng ký lái thử](#) hoặc [Nhận báo giá](javascript:alert(1)).',
            '- Legacy routes: [Đặt lịch tư vấn](/dat-lich?type=consult) và [Phụ kiện BMW](/phu-kien).',
            '- Nhãn không rõ route: [Gói đặc quyền nội bộ chưa mở]().',
        ]);

        ShowroomAssistant::fake([$dirtyReply])->preventStrayPrompts();

        $response = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tư vấn giúp tôi BMW 330i M Sport và ưu đãi',
            'visitor_id' => 'phase221-link-sanitizer-01',
        ])->assertOk();

        $answer = (string) $response->json('answer');
        $this->assertStringNotContainsString(']()', $answer);
        $this->assertStringNotContainsString('(#)', $answer);
        $this->assertStringNotContainsString('javascript:', $answer);
        $this->assertStringNotContainsString('bmw-330i-fake-slug', $answer);
        $this->assertStringNotContainsString('bai-ao-khong-co', $answer);
        $this->assertStringNotContainsString('/dat-lich', $answer);
        $this->assertStringNotContainsString('/phu-kien', $answer);
        $this->assertStringContainsString('[BMW 330i M Sport](/catalog/bmw-330i-m-sport)', $answer);
        $this->assertStringContainsString('[Ưu đãi BMW mùa thu](/tim-hieu-them/uu-dai-bmw-mua-thu)', $answer);
        $this->assertStringContainsString('[Đăng ký lái thử](/booking?type=test_drive)', $answer);
        $this->assertStringContainsString('[Nhận báo giá](/booking?type=quote)', $answer);
        $this->assertStringContainsString('[Đặt lịch tư vấn](/booking?type=consult)', $answer);
        $this->assertStringContainsString('[Phụ kiện BMW](/accessories)', $answer);
        $this->assertStringContainsString('**Gói đặc quyền nội bộ chưa mở**', $answer);
    }

    public function test_phase22_1_widget_persistence_shadcn_grid_and_mobile_contracts(): void
    {
        $widgetBlade = file_get_contents(resource_path('views/components/public/ai-assistant-widget.blade.php')) ?: '';
        $this->assertStringContainsString('sm:w-[420px]', $widgetBlade);
        $this->assertStringContainsString('w-[calc(100vw-1.5rem)]', $widgetBlade);
        $this->assertStringContainsString('max-w-[420px]', $widgetBlade);
        $this->assertStringContainsString('grid-cols-2', $widgetBlade);
        $this->assertStringContainsString('message.actions.length % 2 === 1 && actionIndex === 0', $widgetBlade);
        $this->assertStringContainsString('col-span-2', $widgetBlade);
        $this->assertStringContainsString('scrollbar-none', $widgetBlade);
        $this->assertStringContainsString('visiblePartText(part, message.revealedChars).length > 0', $widgetBlade);
        $this->assertStringNotContainsString('size="h-7 w-7"', $widgetBlade);
        $this->assertStringNotContainsString('x-html', $widgetBlade);
        $this->assertStringNotContainsString('href="#"', $widgetBlade);
        $this->assertStringNotContainsString('rounded-', $widgetBlade);

        $appJs = file_get_contents(resource_path('js/app.js')) ?: '';
        $this->assertStringContainsString('maxStoredMessages: 16', $appJs);
        $this->assertStringContainsString('normalizeSuggestionList(', $appJs);
        $this->assertStringContainsString('normalizeActionList(', $appJs);
        $this->assertStringContainsString('prioritizeAndLimitActions(', $appJs);
        $this->assertStringContainsString("value === '#'", $appJs);
        $this->assertStringContainsString("value.startsWith('#')", $appJs);
        $this->assertMatchesRegularExpression('/this\.appendAssistantMessage\([\s\S]*?\);\s*this\.pruneMessages\(\);\s*this\.persistState\(\);/', $appJs);
    }
}
