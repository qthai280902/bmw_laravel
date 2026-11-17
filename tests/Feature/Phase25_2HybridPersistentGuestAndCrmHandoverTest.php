<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Enums\AppointmentType;
use App\Enums\VehicleType;
use App\Livewire\Admin\AppointmentTable;
use App\Models\AiChatMessage;
use App\Models\AiChatSession;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\MessageRole;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;
use Tests\TestCase;

class Phase25_2HybridPersistentGuestAndCrmHandoverTest extends TestCase
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

        $this->withCredentials();
    }

    public function test_server_cookie_rehydrates_guest_history_across_lost_localstorage_and_resets_cleanly(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        ShowroomAssistant::fake([
            "Mẫu [{$car->name}](/catalog/{$car->slug}) đang có giá niêm yết 2,499 tỷ VNĐ.",
        ])->preventStrayPrompts();

        $askResponse = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Giá BMW 330i M Sport bao nhiêu?',
        ]);

        $askResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertCookie('bmw_ai_visitor_id');

        $cookie = $askResponse->getCookie('bmw_ai_visitor_id');
        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));

        $visitorId = (string) $cookie->getValue();
        $this->assertNotSame('', $visitorId);
        $this->assertSame($visitorId, $askResponse->json('visitor_id'));

        // Simulate lost localStorage: call /history ONLY using HttpOnly server cookie
        $historyResponse = $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->getJson(route('ai.showroom-assistant.history'));

        $historyResponse->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('visitor_id', $visitorId)
            ->assertJsonPath('restored', true)
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.role', 'user')
            ->assertJsonPath('messages.0.content', 'Giá BMW 330i M Sport bao nhiêu?')
            ->assertJsonPath('messages.1.role', 'assistant');

        // Reset via cookie ONLY (without body visitor_id)
        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->postJson(route('ai.showroom-assistant.reset'))
            ->assertOk()
            ->assertJsonPath('reset', true)
            ->assertJsonPath('visitor_id', $visitorId);

        // Even after Cache flush, DB reset_after_message_id prevents rehydrating closed conversation
        Cache::flush();

        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->getJson(route('ai.api.assistant.history'))
            ->assertOk()
            ->assertJsonPath('restored', false)
            ->assertJsonCount(0, 'messages');
    }

    public function test_multi_turn_conversational_memory_persists_via_cookie_without_body_visitor_id(): void
    {
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 330i M Sport',
            'slug' => 'bmw-330i-m-sport',
            'type' => VehicleType::CAR,
            'price' => 2499000000,
            'is_active' => true,
        ]);

        ShowroomAssistant::fake([
            'Giá BMW 330i M Sport là 2,499 tỷ VNĐ.',
            'Chi phí lăn bánh dự kiến tại TP.HCM khoảng 2,77 tỷ VNĐ.',
        ])->preventStrayPrompts();

        $turn1 = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Giá BMW 330i',
        ])->assertOk();

        $visitorId = (string) $turn1->getCookie('bmw_ai_visitor_id')?->getValue();
        $this->assertNotSame('', $visitorId);

        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->postJson(route('ai.showroom-assistant.ask'), [
                'message' => 'Giá lăn bánh là bao nhiêu?',
            ])->assertOk();

        ShowroomAssistant::assertPrompted(function (AgentPrompt $prompt): bool {
            if (! Str::contains($prompt->prompt, 'Giá lăn bánh là bao nhiêu?')) {
                return false;
            }

            $history = collect($prompt->agent->messages())->values();

            return $history->count() === 2
                && $history[0] instanceof Message
                && $history[0]->role === MessageRole::User
                && Str::contains((string) $history[0]->content, 'Giá BMW 330i')
                && $history[1] instanceof Message
                && $history[1]->role === MessageRole::Assistant;
        });

        $session = AiChatSession::query()->where('visitor_id', $visitorId)->firstOrFail();
        $this->assertSame(4, $session->message_count);
    }

    public function test_crm_handover_links_ai_chat_to_booking_and_admin_can_inspect_conversation_modal(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $category = Category::factory()->create(['name' => 'Sedan', 'slug' => 'sedan']);
        $car = Product::factory()->for($category)->create([
            'name' => 'BMW 530i Sedan',
            'slug' => 'bmw-530i-sedan',
            'type' => VehicleType::CAR,
            'price' => 3289000000,
            'is_active' => true,
        ]);

        ShowroomAssistant::fake([
            'BMW 530i Sedan là mẫu sedan hạng sang cỡ trung rất được ưa chuộng.',
            'Quý khách có thể đặt lịch lái thử trực tiếp tại showroom.',
        ])->preventStrayPrompts();

        $turn1 = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tư vấn giúp tôi mẫu BMW 530i Sedan',
            'page_url' => route('products.show', $car->slug),
        ])->assertOk();

        $visitorId = (string) $turn1->getCookie('bmw_ai_visitor_id')?->getValue();

        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->postJson(route('ai.showroom-assistant.ask'), [
                'message' => 'Tôi muốn đăng ký lái thử cuối tuần này để đặt cọc sớm',
            ])->assertOk();

        // Form renders hidden visitor_id hydrated from cookie even without JS
        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->get(route('appointments.create', ['type' => 'test_drive', 'product_id' => $car->id]))
            ->assertOk()
            ->assertSee($visitorId, false);

        // Submit booking using cookie handover (without explicit ai_visitor_id field)
        $this->withCookie('bmw_ai_visitor_id', $visitorId)
            ->post(route('appointments.store'), [
                'guest_name' => 'Tran Van Handover',
                'guest_phone' => '0909555666',
                'guest_email' => 'handover@example.com',
                'product_id' => $car->id,
                'type' => AppointmentType::TestDrive->value,
                'appointment_date' => now()->addDays(2)->format('Y-m-d H:i'),
                'notes' => 'Đặt lịch lái thử cuối tuần',
            ])
            ->assertRedirect(route('appointments.success'));

        $session = AiChatSession::query()->where('visitor_id', $visitorId)->firstOrFail();
        $appointment = Appointment::query()->where('guest_phone', '0909555666')->firstOrFail();

        $this->assertSame($visitorId, $appointment->ai_visitor_id);
        $this->assertSame($session->id, $appointment->ai_conversation_id);
        $this->assertNotNull($appointment->ai_chat_summary);
        $this->assertStringContainsString('BMW 530i Sedan', (string) $appointment->ai_chat_summary);
        $this->assertSame('appointment', $session->linked_source_type);
        $this->assertSame($appointment->id, $session->linked_source_id);

        // Direct model creation with ai_visitor_id also runs handover idempotently via AppointmentObserver
        $directSession = AiChatSession::create([
            'visitor_id' => 'visitor-direct-handover-99',
            'first_seen_at' => now()->subMinutes(15),
            'last_seen_at' => now()->subMinutes(10),
            'message_count' => 2,
            'last_message_preview' => 'Hỏi mua ngay BMW 530i Sedan',
        ]);
        AiChatMessage::create([
            'ai_chat_session_id' => $directSession->id,
            'role' => 'user',
            'content' => 'Tôi cần mua ngay BMW 530i Sedan và đặt cọc trong tuần này.',
        ]);

        $directAppointment = Appointment::create([
            'guest_name' => 'Le Observer Lead',
            'guest_phone' => '0909777888',
            'product_id' => $car->id,
            'type' => AppointmentType::Quote->value,
            'appointment_date' => now()->addDays(1),
            'ai_visitor_id' => 'visitor-direct-handover-99',
        ]);

        $directAppointment->refresh();
        $this->assertSame($directSession->id, $directAppointment->ai_conversation_id);
        $this->assertSame('hot', $directAppointment->lead_priority);

        // Admin inspects AI conversation modal inside Livewire AppointmentTable
        Livewire::actingAs($admin)
            ->test(AppointmentTable::class)
            ->assertSee('ĐÃ CHAT AI')
            ->assertSee('Xem lịch sử chat AI')
            ->call('inspectAiConversation', $appointment->id)
            ->assertSee('BÀN GIAO NGỮ CẢNH AI')
            ->assertSee('Tư vấn giúp tôi mẫu BMW 530i Sedan')
            ->assertSee('Quý khách có thể đặt lịch lái thử trực tiếp tại showroom.');
    }

    public function test_prune_ai_conversations_command_deletes_expired_guest_sessions_and_preserves_crm_lead_sessions(): void
    {
        $expiredGuestSession = AiChatSession::create([
            'visitor_id' => 'expired-guest-65d',
            'first_seen_at' => now()->subDays(66),
            'last_seen_at' => now()->subDays(65),
            'message_count' => 2,
        ]);
        $expiredGuestSession->forceFill([
            'updated_at' => now()->subDays(65),
        ])->saveQuietly();
        $expiredGuestMsg = AiChatMessage::create([
            'ai_chat_session_id' => $expiredGuestSession->id,
            'role' => 'user',
            'content' => 'Tin nhắn vãng lai cũ 65 ngày',
        ]);

        $recentGuestSession = AiChatSession::create([
            'visitor_id' => 'recent-guest-10d',
            'first_seen_at' => now()->subDays(10),
            'last_seen_at' => now()->subDays(10),
            'message_count' => 2,
        ]);
        $recentGuestSession->forceFill([
            'updated_at' => now()->subDays(10),
        ])->saveQuietly();

        $crmLinkedSession = AiChatSession::create([
            'visitor_id' => 'crm-lead-65d',
            'linked_source_type' => 'appointment',
            'linked_source_id' => 999,
            'status' => AiChatSession::STATUS_CONVERTED,
            'first_seen_at' => now()->subDays(66),
            'last_seen_at' => now()->subDays(65),
            'message_count' => 2,
        ]);
        $crmLinkedSession->forceFill([
            'updated_at' => now()->subDays(65),
        ])->saveQuietly();
        $crmMsg = AiChatMessage::create([
            'ai_chat_session_id' => $crmLinkedSession->id,
            'role' => 'user',
            'content' => 'Tin nhắn khách đã đặt lịch 65 ngày trước',
        ]);

        $appointmentWithOldChat = Appointment::factory()->guest()->create([
            'ai_visitor_id' => 'crm-lead-65d',
            'ai_conversation_id' => $crmLinkedSession->id,
            'appointment_date' => now()->subDays(60),
        ]);

        $this->artisan('ai:prune-conversations', ['--days' => 60])
            ->expectsOutputToContain('Đã dọn dẹp 1 phiên hội thoại vãng lai quá 60 ngày (1 tin nhắn). Bảo toàn 1 phiên hội thoại gắn với Lead CRM.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('ai_chat_sessions', ['id' => $expiredGuestSession->id]);
        $this->assertDatabaseMissing('ai_chat_messages', ['id' => $expiredGuestMsg->id]);
        $this->assertDatabaseHas('ai_chat_sessions', ['id' => $recentGuestSession->id]);
        $this->assertDatabaseHas('ai_chat_sessions', ['id' => $crmLinkedSession->id]);
        $this->assertDatabaseHas('ai_chat_messages', ['id' => $crmMsg->id]);
        $this->assertDatabaseHas('appointments', ['id' => $appointmentWithOldChat->id]);
    }

    public function test_cross_tab_sync_contracts_and_guest_first_navigation_render_cleanly(): void
    {
        $appJs = file_get_contents(resource_path('js/app.js')) ?: '';

        $this->assertStringContainsString('bmw_ai_assistant_sync', $appJs);
        $this->assertStringContainsString('BroadcastChannel', $appJs);
        $this->assertStringContainsString("window.addEventListener('storage'", $appJs);
        $this->assertStringContainsString('rehydrateFromServer()', $appJs);
        $this->assertStringContainsString('syncFromLocalStorage()', $appJs);
        $this->assertStringContainsString('bmw_ai_visitor_id', $appJs);
        $this->assertStringContainsString('visitor_id: this.ensureVisitorId()', $appJs);
        $this->assertStringContainsString('input[name="ai_visitor_id"]', $appJs);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Cổng quản trị');
    }
}
