<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\Ai\CloudflareEmbeddingService;

class ProductObserver
{
    public function __construct(private readonly CloudflareEmbeddingService $embeddingService) {}

    public function saved(Product $product): void
    {
        if (! $this->embeddingService->isEnabled() || $product->is_active !== true) {
            return;
        }

        $parts = $product->toEmbeddingParts();
        $this->embeddingService->syncModel($product, $parts['content'], $parts['title']);
    }
}
