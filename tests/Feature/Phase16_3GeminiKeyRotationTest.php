<?php

namespace Tests\Feature;

use App\Ai\Agents\ShowroomAssistant;
use App\Services\Ai\GeminiKeyPool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Exceptions\RateLimitedException;
use Tests\TestCase;

class Phase16_3GeminiKeyRotationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'showroom_ai.enabled' => true,
            'showroom_ai.provider' => 'gemini',
            'showroom_ai.model' => null,
            'showroom_ai.gemini_key_rotation' => 'round_robin',
            'showroom_ai.gemini_key_cooldown_seconds' => 120,
            'showroom_ai.rate_limit_fallback_message' => 'Rate limit fallback for tests.',
            'ai.providers.gemini.key' => null,
        ]);
    }

    public function test_gemini_key_pool_returns_empty_list_without_configured_keys(): void
    {
        $this->configureGeminiKeys();

        $this->assertSame([], app(GeminiKeyPool::class)->keys());
    }

    public function test_gemini_key_pool_supports_single_legacy_key(): void
    {
        $this->configureGeminiKeys(primary: 'legacy-primary-key');

        $this->assertSame(['legacy-primary-key'], app(GeminiKeyPool::class)->keys());
    }

    public function test_gemini_key_pool_parses_comma_separated_keys(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two,key-three');

        $this->assertSame(['key-one', 'key-two', 'key-three'], app(GeminiKeyPool::class)->keys());
    }

    public function test_gemini_key_pool_deduplicates_trims_and_ignores_empty_values(): void
    {
        $this->configureGeminiKeys(
            primary: ' key-one ',
            additional: "key-one, , key-two|key-three\nkey-two",
            aiProviderKey: 'key-three',
        );

        $this->assertSame(['key-one', 'key-two', 'key-three'], app(GeminiKeyPool::class)->keys());
    }

    public function test_gemini_key_pool_has_no_parser_hard_limit(): void
    {
        $keys = collect(range(1, 20))
            ->map(fn (int $index): string => 'key-'.$index)
            ->implode(',');

        $this->configureGeminiKeys(additional: $keys);

        $this->assertCount(20, app(GeminiKeyPool::class)->keys());
    }

    public function test_round_robin_rotation_does_not_always_start_with_first_key(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two,key-three');

        $pool = app(GeminiKeyPool::class);

        $this->assertSame([0, 1, 2], collect($pool->candidateSequence())->pluck('index')->all());
        $this->assertSame([1, 2, 0], collect($pool->candidateSequence())->pluck('index')->all());
    }

    public function test_cooldown_skips_rate_limited_key_and_can_exhaust_all_candidates(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two');

        $pool = app(GeminiKeyPool::class);
        $candidates = $pool->candidates();

        $pool->markRateLimited($candidates[0]);

        $this->assertSame([1], collect($pool->availableCandidates())->pluck('index')->all());

        $pool->markRateLimited($candidates[1]);

        $this->assertSame([], $pool->availableCandidates());
    }

    public function test_first_rate_limited_key_fails_over_to_next_key_without_real_ai_call(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two');
        Log::spy();

        $attempts = 0;

        ShowroomAssistant::fake(function () use (&$attempts): string {
            $attempts++;

            if ($attempts === 1) {
                throw RateLimitedException::forProvider('gemini');
            }

            return 'Rotated Gemini answer.';
        })->preventStrayPrompts();

        $response = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tu van BMW 330i',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('answer', 'Rotated Gemini answer.');

        $this->assertSame(2, $attempts);
        $this->assertStringNotContainsString('key-one', $response->getContent());
        $this->assertStringNotContainsString('key-two', $response->getContent());

        Log::shouldHaveReceived('warning')->withArgs(function (string $message, array $context): bool {
            return $message === 'Showroom AI assistant Gemini key rate limited.'
                && ($context['reason'] ?? null) === 'rate_limited'
                && ($context['key_index'] ?? null) === 0
                && ! Str::contains(json_encode($context), ['key-one', 'key-two']);
        });
    }

    public function test_all_rate_limited_keys_return_rate_limit_fallback(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two');

        $attempts = 0;

        ShowroomAssistant::fake(function () use (&$attempts): never {
            $attempts++;

            throw RateLimitedException::forProvider('gemini');
        })->preventStrayPrompts();

        $response = $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tu van SUV/SAV',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonPath('reason', 'rate_limited')
            ->assertJsonPath('answer', 'Rate limit fallback for tests.');

        $this->assertSame(2, $attempts);
        $this->assertStringNotContainsString('key-one', $response->getContent());
        $this->assertStringNotContainsString('key-two', $response->getContent());
    }

    public function test_all_keys_already_in_cooldown_return_rate_limit_fallback_without_prompting_ai(): void
    {
        $this->configureGeminiKeys(additional: 'key-one,key-two');

        $pool = app(GeminiKeyPool::class);

        foreach ($pool->candidates() as $candidate) {
            $pool->markRateLimited($candidate);
        }

        ShowroomAssistant::fake(['This should not be used.'])->preventStrayPrompts();

        $this->postJson(route('ai.showroom-assistant.ask'), [
            'message' => 'Tu van BMW Motorrad',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'fallback')
            ->assertJsonPath('reason', 'rate_limited')
            ->assertJsonPath('answer', 'Rate limit fallback for tests.');

        ShowroomAssistant::assertNeverPrompted();
    }

    public function test_env_example_documents_multi_key_placeholders_without_real_secret_values(): void
    {
        $envExample = file_get_contents(base_path('.env.example'));

        $this->assertIsString($envExample);
        $this->assertStringContainsString('GEMINI_API_KEY=', $envExample);
        $this->assertStringContainsString('GEMINI_API_KEYS=', $envExample);
        $this->assertStringContainsString('GEMINI_KEY_COOLDOWN_SECONDS=120', $envExample);
        $this->assertStringContainsString('GEMINI_KEY_ROTATION=round_robin', $envExample);
        $this->assertStringNotContainsString('APP_KEY=base64:', $envExample);
        $this->assertStringNotContainsString('AIza', $envExample);
    }

    private function configureGeminiKeys(?string $primary = null, ?string $additional = null, ?string $aiProviderKey = null): void
    {
        config([
            'showroom_ai.gemini_keys.primary' => $primary,
            'showroom_ai.gemini_keys.additional' => $additional,
            'ai.providers.gemini.key' => $aiProviderKey,
        ]);
    }
}
