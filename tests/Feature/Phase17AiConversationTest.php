<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\VehicleType;
use App\Models\AccessoryOrder;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase17AiConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => null,
            'showroom_ai.gemini_keys.primary' => null,
            'showroom_ai.gemini_keys.additional' => null,
            'ai.providers.gemini.key' => 'testing-gemini-key',
        ]);
    }

    public function test_guest_and_non_admin_cannot_view_ai_conversations(): void
    {
        $this->get(route('admin.ai-conversations.index'))
            ->assertRedirect(route('login'));

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)
            ->get(route('admin.ai-conversations.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_ai_conversation_list_and_detail(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $session = AiChatSession::create([
            'visitor_id' => 'visitor-admin-view',
            'ip_address' => '203.0.113.44',
            'ip_hash' => $this->ipHash('203.0.113.44'),
            'first_seen_at' => now(),
            'last_seen_at' => now(),
            'message_count' => 2,
            'last_message_preview' => 'Tư vấn BMW 330i',
            'last_intent' => 'sedan',
        ]);
        AiChatMessage::create([
            'ai_chat_session_id' => $session->id,
            'role' => 'user',
            'content' => 'Tư vấn BMW 330i',
            'content_preview' => 'Tư vấn BMW 330i',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.index'))
            ->assertOk()
            ->assertSee('Lịch sử trợ lý', false)
            ->assertSee('Tư vấn BMW 330i');

        $this->actingAs($admin)
            ->get(route('admin.ai-conversations.show', $session))
            ->assertOk()
            ->assertSee('Timeline chat')
            ->assertSee('Visitor profile')
            ->assertSee('Tư vấn BMW 330i');
    }

    public function test_ai_request_logs_session_messages_visitor_id_and_ip_hash(): void
    {
        $this->createVehicle(['name' => 'BMW 330i Sedan', 'slug' => 'bmw-330i-sedan']);

        ShowroomAssistant::fake(['BMW 330i phù hợp cho nhu cầu đi làm hằng ngày.'])->preventStrayPrompts();

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.22'])
            ->withHeader('User-Agent', 'Phase17 Browser')
            ->postJson(route('ai.showroom-assistant.ask'), [
                'message' => 'Tư vấn giúp tôi BMW 330i sedan',
                'visitor_id' => 'visitor-phase17-001',
                'page_url' => 'http://localhost/catalog/bmw-330i-sedan',
                'referrer' => 'http://localhost/catalog',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonStructure(['conversation_id']);

        $session = AiChatSession::query()->where('visitor_id', 'visitor-phase17-001')->firstOrFail();

        $this->assertSame('203.0.113.22', $session->ip_address);
        $this->assertNotNull($session->ip_hash);
        $this->assertSame('sedan', $session->last_intent);
        $this->assertSame(2, $session->message_count);
        $this->assertCount(2, $session->messages);
        $this->assertDatabaseHas('ai_chat_messages', [
            'ai_chat_session_id' => $session->id,
            'role' => 'assistant',
            'response_reason' => 'ok',
            'page_path' => '/catalog/bmw-330i-sedan',
        ]);
    }

    public function test_appointment_submit_with_ai_visitor_id_links_session_to_customer_name(): void
    {
        $product = $this->createVehicle();
        $session = AiChatSession::create([
            'visitor_id' => 'visitor-booking-001',
            'ip_address' => '203.0.113.23',
            'ip_hash' => $this->ipHash('203.0.113.23'),
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(5),
            'message_count' => 2,
            'last_message_preview' => 'Muốn lái thử BMW 330i',
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.23'])
            ->post(route('appointments.store'), [
                'guest_name' => 'Anh Thai',
                'guest_phone' => '0909000111',
                'guest_email' => 'thai@example.com',
                'product_id' => $product->id,
                'type' => 'test_drive',
                'appointment_date' => now()->addDay()->format('Y-m-d H:i'),
                'ai_visitor_id' => 'visitor-booking-001',
            ])
            ->assertRedirect(route('appointments.success'));

        $appointment = Appointment::query()->firstOrFail();
        $session->refresh();

        $this->assertSame('visitor-booking-001', $appointment->ai_visitor_id);
        $this->assertSame('appointment', $session->linked_source_type);
        $this->assertSame($appointment->id, $session->linked_source_id);
        $this->assertSame('visitor_id', $session->link_confidence);
        $this->assertSame('Anh Thai', $session->displayLabel());
    }

    public function test_accessory_order_submit_with_ai_visitor_id_links_session(): void
    {
        $accessory = $this->createAccessory();
        $session = AiChatSession::create([
            'visitor_id' => 'visitor-order-001',
            'ip_address' => '203.0.113.24',
            'ip_hash' => $this->ipHash('203.0.113.24'),
            'first_seen_at' => now()->subMinutes(10),
            'last_seen_at' => now()->subMinutes(5),
            'message_count' => 2,
            'last_message_preview' => 'Tôi muốn mua phụ kiện',
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.24'])
            ->post(route('accessory-orders.store', $accessory->slug), [
                'customer_name' => 'Chi Linh',
                'customer_phone' => '0909000222',
                'customer_address' => '1 Nguyen Hue',
                'customer_email' => 'linh@example.com',
                'quantity' => 1,
                'ai_visitor_id' => 'visitor-order-001',
            ])
            ->assertRedirect(route('accessory-orders.create', $accessory->slug));

        $order = AccessoryOrder::query()->firstOrFail();
        $session->refresh();

        $this->assertSame('visitor-order-001', $order->ai_visitor_id);
        $this->assertSame('accessory_order', $session->linked_source_type);
        $this->assertSame($order->id, $session->linked_source_id);
        $this->assertSame('Chi Linh', $session->displayLabel());
    }

    public function test_ip_recent_fallback_links_only_one_recent_session_not_all_same_ip(): void
    {
        $product = $this->createVehicle();
        $ipHash = $this->ipHash('203.0.113.25');
        $older = AiChatSession::create([
            'ip_address' => '203.0.113.25',
            'ip_hash' => $ipHash,
            'first_seen_at' => now()->subHours(2),
            'last_seen_at' => now()->subHours(2),
            'message_count' => 2,
            'last_message_preview' => 'Session cũ cùng IP',
        ]);
        $recent = AiChatSession::create([
            'ip_address' => '203.0.113.25',
            'ip_hash' => $ipHash,
            'first_seen_at' => now()->subMinutes(20),
            'last_seen_at' => now()->subMinutes(10),
            'message_count' => 2,
            'last_message_preview' => 'Session gần nhất cùng IP',
        ]);

        $this
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.25'])
            ->post(route('appointments.store'), [
                'guest_name' => 'Anh Minh',
                'guest_phone' => '0909000333',
                'product_id' => $product->id,
                'type' => 'quote',
                'appointment_date' => now()->addDay()->format('Y-m-d H:i'),
            ])
            ->assertRedirect(route('appointments.success'));

        $older->refresh();
        $recent->refresh();

        $this->assertNull($older->linked_source_type);
        $this->assertSame('appointment', $recent->linked_source_type);
        $this->assertSame('ip_recent', $recent->link_confidence);
    }

    public function test_ai_logging_redacts_secret_like_values_from_stored_content(): void
    {
        ShowroomAssistant::fake(['Không cần xử lý key trong chat.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'GEMINI_API_KEY=AIza'.str_repeat('a', 30),
            'visitor_id' => 'visitor-secret-001',
        ])->assertOk();

        $content = AiChatMessage::query()
            ->where('role', 'user')
            ->value('content');

        $this->assertStringNotContainsString('AIza', (string) $content);
        $this->assertStringContainsString('[secret hidden]', (string) $content);
    }

    public function test_widget_script_generates_and_submits_ai_visitor_id(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString('bmw_ai_visitor_id', $script);
        $this->assertStringContainsString('visitor_id: this.ensureVisitorId()', $script);
        $this->assertStringContainsString('input[name="ai_visitor_id"]', $script);
    }

    private function createVehicle(array $attributes = []): Product
    {
        $category = Category::factory()->create([
            'name' => 'BMW Sedan',
            'slug' => 'bmw-sedan',
        ]);

        return Product::factory()
            ->for($category)
            ->create(array_merge([
                'type' => VehicleType::CAR,
                'is_active' => true,
            ], $attributes));
    }

    private function createAccessory(array $attributes = []): Product
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện BMW',
            'slug' => 'phu-kien-bmw',
        ]);

        return Product::factory()
            ->for($category)
            ->create(array_merge([
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
            ], $attributes));
    }

    private function ipHash(string $ipAddress): string
    {
        return hash('sha256', $ipAddress.'|'.config('app.key'));
    }
}
