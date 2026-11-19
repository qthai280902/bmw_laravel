<?php

namespace App\Observers;

use App\Models\ProductImage;
use App\Services\Cache\ShowroomCacheService;
use App\Services\Media\LocalMediaManager;

class ProductImageObserver
{
    public function __construct(
        private readonly LocalMediaManager $mediaManager,
        private readonly ShowroomCacheService $cacheService,
    ) {}

    public function saved(ProductImage $image): void
    {
        $this->cacheService->flushCatalog();
    }

    public function updated(ProductImage $image): void
    {
        if ($image->wasChanged('path')) {
            $oldPath = $image->getOriginal('path');
            $this->mediaManager->deleteIfUnreferenced(
                rawPath: is_string($oldPath) ? $oldPath : null,
                ignoreProductImageId: (int) $image->id,
            );
        }

        $this->cacheService->flushCatalog();
    }

    public function deleted(ProductImage $image): void
    {
        $this->mediaManager->deleteIfUnreferenced(
            rawPath: $image->path,
            ignoreProductImageId: (int) $image->id,
        );

        $this->cacheService->flushCatalog();
    }
}
