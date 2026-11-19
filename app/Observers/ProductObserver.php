<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Media\LocalMediaManager;

class ProductObserver
{
    public static bool $deferSyncToQueue = false;

    /**
     * Collect child image paths prior to DB cascade deletion so physical files can be cleaned up safely.
     *
     * @var array<int, list<string>>
     */
    private static array $pendingDeletionImagePaths = [];

    public function __construct(
        private readonly CloudflareEmbeddingService $embeddingService,
        private ?LocalMediaManager $mediaManager = null,
        private ?ShowroomCacheService $cacheService = null,
    ) {}

    /**
     * Suppress synchronous embedding inside Admin CRUD so debounced queue sync runs once.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function withoutImmediateSync(callable $callback): mixed
    {
        $previous = self::$deferSyncToQueue;
        self::$deferSyncToQueue = true;

        try {
            return $callback();
        } finally {
            self::$deferSyncToQueue = $previous;
        }
    }

    public function saved(Product $product): void
    {
        $this->cacheService()->flushCatalog();

        if (self::$deferSyncToQueue || ! $this->embeddingService->isEnabled() || $product->is_active !== true) {
            return;
        }

        $parts = $product->toEmbeddingParts();
        $this->embeddingService->syncModel($product, $parts['content'], $parts['title']);
    }

    public function deleting(Product $product): void
    {
        $paths = $product->images()->pluck('path')
            ->filter(static fn (mixed $path): bool => is_string($path) && trim($path) !== '')
            ->values()
            ->all();

        self::$pendingDeletionImagePaths[(int) $product->id] = $paths;
    }

    public function deleted(Product $product): void
    {
        $productId = (int) $product->id;
        $paths = self::$pendingDeletionImagePaths[$productId] ?? [];
        unset(self::$pendingDeletionImagePaths[$productId]);

        foreach ($paths as $path) {
            $this->mediaManager()->deleteIfUnreferenced(
                rawPath: $path,
                ignoreProductId: $productId,
            );
        }

        $this->cacheService()->flushCatalog();
    }

    public function restored(Product $product): void
    {
        $this->cacheService()->flushCatalog();
    }

    private function mediaManager(): LocalMediaManager
    {
        return $this->mediaManager ??= app(LocalMediaManager::class);
    }

    private function cacheService(): ShowroomCacheService
    {
        return $this->cacheService ??= app(ShowroomCacheService::class);
    }
}
