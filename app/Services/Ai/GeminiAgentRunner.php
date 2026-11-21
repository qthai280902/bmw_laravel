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
    public const DEFAULT_PRIMARY_MODEL = 'gemini-3.1-flash-lite';

    public const DEFAULT_FALLBACK_MODEL = 'gemini-3.5-flash-lite';

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

    public function fallbackModel(?string $primaryModel = null): ?string
    {
        $fallback = config('showroom_ai.fallback_model');

        if (! filled($fallback)) {
            return null;
        }

        $normalized = trim((string) $fallback);
        $resolvedPrimary = $primaryModel ?? $this->configuredModel();

        if ($normalized === '' || ($resolvedPrimary !== null && $normalized === $resolvedPrimary)) {
            return null;
        }

        return $normalized;
    }

    public function defaultTimeoutSeconds(): int
    {
        return max(5, (int) config('showroom_ai.timeout_seconds', 20));
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
     * Execute an AI agent callback across rotated Gemini keys or configured provider,
     * with automatic fallback to secondary Gemini model when primary fails/throttles/times out.
     *
     * @template T
     *
     * @param  Closure(string, ?string): T  $callback
     * @return array{ok: bool, result?: T, reason?: string, model?: ?string}
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
                Context::push('ai_steps', "{$operation}:provider:{$provider}");

                return [
                    'ok' => true,
                    'result' => $callback($provider, $model),
                    'model' => $model,
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

        $primaryOutcome = $this->executeAcrossCandidates($candidates, $callback, $model, $operation, markRateLimit: true);
        if ($primaryOutcome['ok']) {
            return $primaryOutcome;
        }

        $fallbackModel = $this->fallbackModel($model);
        if ($fallbackModel !== null && ($primaryOutcome['retryable'] ?? false)) {
            Context::push('ai_steps', "{$operation}:fallback_model:{$fallbackModel}");
            Log::warning('Primary Gemini model exhausted keys; switching to fallback model.', [
                'operation' => $operation,
                'primary_model' => $model ?? self::DEFAULT_PRIMARY_MODEL,
                'fallback_model' => $fallbackModel,
            ]);

            $fallbackCandidates = $this->geminiKeyPool->availableCandidates();
            if ($fallbackCandidates === []) {
                $fallbackCandidates = $candidates;
            }

            $fallbackOutcome = $this->executeAcrossCandidates($fallbackCandidates, $callback, $fallbackModel, $operation, markRateLimit: false);
            if ($fallbackOutcome['ok']) {
                return $fallbackOutcome;
            }

            return [
                'ok' => false,
                'reason' => $fallbackOutcome['reason'] ?? 'provider_error',
            ];
        }

        return [
            'ok' => false,
            'reason' => $primaryOutcome['reason'] ?? 'provider_error',
        ];
    }

    /**
     * @template T
     *
     * @param  array<int, array{index: int, key: string, fingerprint: string}>  $candidates
     * @param  Closure(string, ?string): T  $callback
     * @return array{ok: bool, result?: T, reason?: string, model?: ?string, retryable?: bool}
     */
    private function executeAcrossCandidates(
        array $candidates,
        Closure $callback,
        ?string $model,
        string $operation,
        bool $markRateLimit
    ): array {
        $attempts = 0;
        $rateLimitedAttempts = 0;
        $retryableErrorSeen = false;

        foreach ($candidates as $candidate) {
            $attempts++;

            try {
                Context::push('ai_steps', "{$operation}:attempt:".($model ?? 'default').":key_{$candidate['index']}");

                $result = $this->withGeminiCandidate($candidate, fn (string $providerName): mixed => $callback($providerName, $model));

                return [
                    'ok' => true,
                    'result' => $result,
                    'model' => $model,
                ];
            } catch (RateLimitedException $exception) {
                $rateLimitedAttempts++;
                $retryableErrorSeen = true;

                if ($markRateLimit) {
                    $this->geminiKeyPool->markRateLimited($candidate);
                }

                Log::warning('Gemini key rate limited.', [
                    'operation' => $operation,
                    'model' => $model,
                    'key_index' => $candidate['index'],
                    'exception' => $exception::class,
                    'attempt' => $attempts,
                ]);
            } catch (Throwable $exception) {
                if ($this->isTransientProviderException($exception)) {
                    $retryableErrorSeen = true;

                    Log::warning('Transient Gemini provider failure; rotating key.', [
                        'operation' => $operation,
                        'model' => $model,
                        'key_index' => $candidate['index'],
                        'exception' => $exception::class,
                    ]);

                    continue;
                }

                Log::warning('Gemini AI request failed.', [
                    'operation' => $operation,
                    'model' => $model,
                    'key_index' => $candidate['index'],
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                return [
                    'ok' => false,
                    'reason' => 'provider_error',
                    'retryable' => false,
                ];
            }
        }

        return [
            'ok' => false,
            'reason' => ($attempts > 0 && $rateLimitedAttempts === $attempts) ? 'rate_limited' : 'provider_error',
            'retryable' => $retryableErrorSeen,
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

        if ($exception instanceof RequestException && $exception->response !== null) {
            $status = $exception->response->status();

            return $status === 429 || $status >= 500;
        }

        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out') || str_contains($message, 'timeout');
    }
}
