<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\Cache\ShowroomCacheService;

class CategoryObserver
{
    public function __construct(
        private readonly ShowroomCacheService $cacheService,
    ) {}

    public function saved(Category $category): void
    {
        $this->cacheService->flushCatalog();
    }

    public function deleted(Category $category): void
    {
        $this->cacheService->flushCatalog();
    }

    public function restored(Category $category): void
    {
        $this->cacheService->flushCatalog();
    }
}
