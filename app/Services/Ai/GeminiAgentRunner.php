<?php

namespace App\Services\Ai;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\AiManager;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

class GeminiAgentRunner
{
    public function __construct(
        private GeminiKeyPool $geminiKeyPool,
    ) {}

    public function configuredProvider(): string
    {
        return trim((string) config('showroom_ai.provider', 'gemini'));
    }

    public function configuredModel(): ?string
    {
        $model = config('showroom_ai.model');

        return filled($model) ? trim((string) $model) : null;
    }

    public function configurationFailureReason(?string $provider = null): ?string
    {
        $provider ??= $this->configuredProvider();

        if (! (bool) config('showroom_ai.enabled', true)) {
            return 'assistant_disabled';
        }

        if ($provider === '') {
            return 'provider_missing';
        }

        if ($provider === 'gemini') {
            return $this->geminiKeyPool->hasKeys() ? null : 'gemini_key_missing';
        }

        return filled(Arr::get(config("ai.providers.{$provider}", []), 'key')) ? null : 'provider_key_missing';
    }

    public function hasKeysForGemini(): bool
    {
        return $this->geminiKeyPool->hasKeys();
    }

    /**
     * Execute an AI agent callback across rotated Gemini keys or configured provider.
     *
     * @template T
     *
     * @param  Closure(string, ?string): T  $callback
     * @return array{ok: bool, result?: T, reason?: string}
     */
    public function run(Closure $callback, string $operation = 'agent_prompt'): array
    {
        $provider = $this->configuredProvider();
        $model = $this->configuredModel();

        if ($reason = $this->configurationFailureReason($provider)) {
            return [
                'ok' => false,
                'reason' => $reason,
            ];
        }

        if ($provider !== 'gemini') {
            try {
                return [
                    'ok' => true,
                    'result' => $callback($provider, $model),
                ];
            } catch (Throwable $exception) {
                Log::warning('AI agent execution failed on non-Gemini provider.', [
                    'operation' => $operation,
                    'provider' => $provider,
                    'exception' => $exception::class,
                ]);

                return [
                    'ok' => false,
                    'reason' => $exception instanceof RateLimitedException ? 'rate_limited' : 'provider_error',
                ];
            }
        }

        $candidates = $this->geminiKeyPool->candidateSequence();
        if ($candidates === []) {
            return [
                'ok' => false,
                'reason' => $this->geminiKeyPool->hasKeys() ? 'rate_limited' : 'gemini_key_missing',
            ];
        }

        $attempts = 0;
        $rateLimitedAttempts = 0;

        foreach ($candidates as $candidate) {
            $attempts++;

            try {
                $result = $this->withGeminiCandidate($candidate, fn (string $providerName): mixed => $callback($providerName, $model));

                return [
                    'ok' => true,
                    'result' => $result,
                ];
            } catch (RateLimitedException $exception) {
                $rateLimitedAttempts++;
                $this->geminiKeyPool->markRateLimited($candidate);

                Log::warning('Gemini key rate limited.', [
                    'operation' => $operation,
                    'key_index' => $candidate['index'],
                    'exception' => $exception::class,
                    'attempt' => $attempts,
                ]);
            } catch (Throwable $exception) {
                if ($this->isTransientProviderException($exception)) {
                    Log::warning('Transient Gemini provider failure; rotating key.', [
                        'operation' => $operation,
                        'key_index' => $candidate['index'],
                        'exception' => $exception::class,
                    ]);

                    continue;
                }

                Log::warning('Gemini AI request failed.', [
                    'operation' => $operation,
                    'key_index' => $candidate['index'],
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'provider_error',
                ];
            }
        }

        return [
            'ok' => false,
            'reason' => ($attempts > 0 && $rateLimitedAttempts === $attempts) ? 'rate_limited' : 'provider_error',
        ];
    }

    /**
     * Execute callback inside a temporary per-candidate Gemini provider configuration.
     *
     * @template T
     *
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     * @param  Closure(string): T  $callback
     * @return T
     */
    public function withGeminiCandidate(array $candidate, Closure $callback): mixed
    {
        Context::add('gemini_key_slot', $candidate['index']);

        $providerName = $this->geminiKeyPool->providerNameFor($candidate);
        $originalConfig = config("ai.providers.{$providerName}");
        $baseConfig = config('ai.providers.gemini', []);

        Config::set("ai.providers.{$providerName}", array_merge($baseConfig, [
            'driver' => 'gemini',
            'key' => $candidate['key'],
        ]));

        app(AiManager::class)->forgetInstance($providerName);

        try {
            return $callback($providerName);
        } finally {
            app(AiManager::class)->forgetInstance($providerName);
            Config::set("ai.providers.{$providerName}", $originalConfig);
        }
    }

    public function isTransientProviderException(Throwable $exception): bool
    {
        if ($exception instanceof ProviderOverloadedException || $exception instanceof ConnectionException) {
            return true;
        }

        return $exception instanceof RequestException
            && $exception->response !== null
            && $exception->response->status() >= 500;
    }
}
