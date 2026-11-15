<?php

namespace App\Observers;

use App\Models\Article;
use App\Services\Ai\CloudflareEmbeddingService;

class ArticleObserver
{
    public function __construct(private readonly CloudflareEmbeddingService $embeddingService) {}

    public function saved(Article $article): void
    {
        if (! $this->embeddingService->isEnabled() || ! $article->isPublished()) {
            return;
        }

        $parts = $article->toEmbeddingParts();
        $this->embeddingService->syncModel($article, $parts['content'], $parts['title']);
    }
}
