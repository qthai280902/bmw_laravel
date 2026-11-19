<?php

namespace App\Listeners;

use App\Events\CatalogEmbeddingSyncRequested;
use App\Models\Article;
use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Cache;

class SyncCatalogEmbeddingListener implements ShouldQueue
{
    use InteractsWithQueue;

    public string $queue = 'default';

    public int $tries = 3;

    public int $debounceSeconds = 30;

    public int $maxWaitSeconds = 120;

    public function __construct(private readonly CloudflareEmbeddingService $embeddingService) {}

    public function debounceId(CatalogEmbeddingSyncRequested $event): string
    {
        return $event->debounceId();
    }

    public function withDelay(CatalogEmbeddingSyncRequested $event): int
    {
        $firstRequestedAt = (int) Cache::get(
            CatalogEmbeddingSyncRequested::FIRST_REQUESTED_PREFIX.$this->debounceId($event),
            $event->requestedAt
        );

        $elapsed = max(0, now()->getTimestamp() - $firstRequestedAt);

        if ($elapsed >= $this->maxWaitSeconds) {
            return 0;
        }

        return min($this->debounceSeconds, max(1, $this->maxWaitSeconds - $elapsed));
    }

    public function handle(CatalogEmbeddingSyncRequested $event): void
    {
        $debounceId = $this->debounceId($event);
        $latestToken = Cache::get(CatalogEmbeddingSyncRequested::LATEST_TOKEN_PREFIX.$debounceId);

        if (is_string($latestToken) && $latestToken !== '' && $latestToken !== $event->debounceToken) {
            return;
        }

        Cache::forget(CatalogEmbeddingSyncRequested::FIRST_REQUESTED_PREFIX.$debounceId);

        if (! $this->embeddingService->isEnabled()) {
            return;
        }

        if ($event->entityType === 'product') {
            $product = Product::query()->with('category')->find($event->entityId);

            if (! $product || $product->is_active !== true) {
                return;
            }

            $parts = $product->toEmbeddingParts();
            $this->embeddingService->syncModel($product, $parts['content'], $parts['title']);

            return;
        }

        if ($event->entityType === 'article') {
            $article = Article::query()->find($event->entityId);

            if (! $article || ! $article->isPublished()) {
                return;
            }

            $parts = $article->toEmbeddingParts();
            $this->embeddingService->syncModel($article, $parts['content'], $parts['title']);
        }
    }
}
