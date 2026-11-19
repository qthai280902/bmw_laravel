<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Ai\CloudflareEmbeddingService;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Media\LocalMediaManager;

class ArticleObserver
{
    public static bool $deferSyncToQueue = false;

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

    public function saved(Article $article): void
    {
        $this->cacheService()->flushArticles();

        if (self::$deferSyncToQueue || ! $this->embeddingService->isEnabled() || ! $article->isPublished()) {
            return;
        }

        $parts = $article->toEmbeddingParts();
        $this->embeddingService->syncModel($article, $parts['content'], $parts['title']);
    }

    public function updated(Article $article): void
    {
        if ($article->wasChanged('cover_image')) {
            $oldCover = $article->getOriginal('cover_image');
            $this->mediaManager()->deleteIfUnreferenced(
                rawPath: is_string($oldCover) ? $oldCover : null,
                ignoreArticleId: (int) $article->id,
            );
        }
    }

    public function deleted(Article $article): void
    {
        $this->mediaManager()->deleteIfUnreferenced(
            rawPath: $article->cover_image,
            ignoreArticleId: (int) $article->id,
        );

        $this->cacheService()->flushArticles();
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
