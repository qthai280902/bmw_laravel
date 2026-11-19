<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CatalogEmbeddingSyncRequested implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public const LATEST_TOKEN_PREFIX = 'catalog_embedding_debounce:latest:';

    public const FIRST_REQUESTED_PREFIX = 'catalog_embedding_debounce:first:';

    public string $debounceToken;

    public int $requestedAt;

    public function __construct(
        public string $entityType,
        public int $entityId,
        ?string $debounceToken = null,
    ) {
        $this->debounceToken = $debounceToken ?: (string) Str::uuid();
        $this->requestedAt = now()->getTimestamp();

        $debounceId = $this->debounceId();
        Cache::put(self::LATEST_TOKEN_PREFIX.$debounceId, $this->debounceToken, 300);

        if (! Cache::has(self::FIRST_REQUESTED_PREFIX.$debounceId)) {
            Cache::put(self::FIRST_REQUESTED_PREFIX.$debounceId, $this->requestedAt, 300);
        }
    }

    public function debounceId(): string
    {
        return "{$this->entityType}:{$this->entityId}";
    }
}
