<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;

class ProductObserver
{
    public static bool $deferSyncToQueue = false;

    public function __construct(private readonly CloudflareEmbeddingService $embeddingService) {}

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
        if (self::$deferSyncToQueue || ! $this->embeddingService->isEnabled() || $product->is_active !== true) {
            return;
        }

        $parts = $product->toEmbeddingParts();
        $this->embeddingService->syncModel($product, $parts['content'], $parts['title']);
    }
}
