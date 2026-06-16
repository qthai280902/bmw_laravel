<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AiAssistantFallbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_gemini_key_returns_fallback_without_prompting_ai(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'ai.providers.gemini.key' => null,
        ]);

        ShowroomAssistant::fake(['This should not be used.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tôi muốn mua BMW tầm 3 tỷ',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonFragment([
                'answer' => config('showroom_ai.fallback_message'),
            ]);

        ShowroomAssistant::assertNeverPrompted();
    }

    public function test_services_gemini_key_is_not_used_for_showroom_assistant_configuration(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'ai.providers.gemini.key' => null,
            'services.gemini.key' => 'testing-services-key',
        ]);

        ShowroomAssistant::fake(['This should not be used.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tu van BMW 330i',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonPath('reason', 'configuration');

        ShowroomAssistant::assertNeverPrompted();
    }

    public function test_ai_exception_returns_friendly_fallback_without_public_stack_trace(): void
    {
        config([
            'showroom_ai.enabled' => true,
            'ai.providers.gemini.key' => 'testing-gemini-key',
        ]);

        ShowroomAssistant::fake(function (): never {
            throw new RuntimeException('Simulated provider outage');
        })->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Có ưu đãi nào mới không?',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonMissing([
                'exception' => RuntimeException::class,
                'message' => 'Simulated provider outage',
            ]);
    }
}
