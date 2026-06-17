<?php

namespace App\Services\Ai;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GeminiKeyPool
{
    private const COOLDOWN_PREFIX = 'showroom_ai:gemini_key_cooldown:';

    private const ROTATION_CACHE_KEY = 'showroom_ai:gemini_key_rotation_cursor';

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return $this->normalizeKeys([
            config('showroom_ai.gemini_keys.primary'),
            config('showroom_ai.gemini_keys.additional'),
            config('ai.providers.gemini.key'),
        ]);
    }

    public function hasKeys(): bool
    {
        return $this->keys() !== [];
    }

    /**
     * @return array<int, array{index: int, key: string, fingerprint: string}>
     */
    public function candidates(): array
    {
        return collect($this->keys())
            ->map(fn (string $key, int $index): array => [
                'index' => $index,
                'key' => $key,
                'fingerprint' => $this->fingerprint($key),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{index: int, key: string, fingerprint: string}>
     */
    public function availableCandidates(): array
    {
        return collect($this->candidates())
            ->reject(fn (array $candidate): bool => $this->isCoolingDown($candidate))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{index: int, key: string, fingerprint: string}>
     */
    public function candidateSequence(): array
    {
        $candidates = $this->availableCandidates();

        if ($candidates === []) {
            return [];
        }

        if ($this->rotationMode() === 'random') {
            return Arr::shuffle($candidates);
        }

        $cursor = (int) Cache::get(self::ROTATION_CACHE_KEY, 0);

        Cache::put(self::ROTATION_CACHE_KEY, $cursor + 1, now()->addDay());

        $start = $cursor % count($candidates);

        return array_merge(
            array_slice($candidates, $start),
            array_slice($candidates, 0, $start),
        );
    }

    /**
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     */
    public function markRateLimited(array $candidate): void
    {
        Cache::put(
            $this->cooldownCacheKey($candidate),
            true,
            now()->addSeconds($this->cooldownSeconds()),
        );
    }

    /**
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     */
    public function isCoolingDown(array $candidate): bool
    {
        return Cache::has($this->cooldownCacheKey($candidate));
    }

    /**
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     */
    public function providerNameFor(array $candidate): string
    {
        return 'gemini-rotation-'.$candidate['index'].'-'.Str::substr($candidate['fingerprint'], 0, 12);
    }

    public function maskKey(string $key): string
    {
        $key = trim($key);

        if (strlen($key) <= 8) {
            return '****';
        }

        return Str::substr($key, 0, 4).'...'.Str::substr($key, -4);
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    public function normalizeKeys(array $values): array
    {
        return collect($values)
            ->flatMap(fn (mixed $value): array => $this->splitValue($value))
            ->map(fn (string $key): string => trim($key))
            ->filter(fn (string $key): bool => $key !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function splitValue(mixed $value): array
    {
        if (is_array($value)) {
            return collect($value)
                ->flatMap(fn (mixed $nested): array => $this->splitValue($nested))
                ->all();
        }

        if (! is_string($value)) {
            return [];
        }

        return preg_split('/[,\|\r\n]+/', $value) ?: [];
    }

    private function rotationMode(): string
    {
        $mode = Str::lower(trim((string) config('showroom_ai.gemini_key_rotation', 'round_robin')));

        return $mode === 'random' ? 'random' : 'round_robin';
    }

    private function cooldownSeconds(): int
    {
        return max(1, (int) config('showroom_ai.gemini_key_cooldown_seconds', 120));
    }

    private function fingerprint(string $key): string
    {
        return sha1($key);
    }

    /**
     * @param  array{index: int, key: string, fingerprint: string}  $candidate
     */
    private function cooldownCacheKey(array $candidate): string
    {
        return self::COOLDOWN_PREFIX.$candidate['index'].':'.$candidate['fingerprint'];
    }
}
