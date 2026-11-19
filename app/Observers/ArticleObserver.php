<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Ai\CloudflareEmbeddingService;

class ArticleObserver
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

    public function saved(Article $article): void
    {
        if (self::$deferSyncToQueue || ! $this->embeddingService->isEnabled() || ! $article->isPublished()) {
            return;
        }

        $parts = $article->toEmbeddingParts();
        $this->embeddingService->syncModel($article, $parts['content'], $parts['title']);
    }
}
