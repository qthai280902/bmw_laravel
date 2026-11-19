<?php

namespace App\Livewire;

use App\Enums\VehicleType;
use App\Livewire\Concerns\WithBmwPagination;
use App\Services\Cache\ShowroomCacheService;
use App\Services\VehicleSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;

class CatalogBrowser extends Component
{
    use WithBmwPagination;

    public ?string $lockedType = null;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'type', except: '')]
    public string $type = '';

    #[Url(as: 'category_id', except: '')]
    public string $categoryId = '';

    #[Url(as: 'min_price', except: '')]
    public string $minPrice = '';

    #[Url(as: 'max_price', except: '')]
    public string $maxPrice = '';

    #[Url(as: 'sort', except: 'latest')]
    public string $sort = 'latest';

    public function mount(?string $lockedType = null): void
    {
        $this->lockedType = $lockedType === 'accessory' ? 'accessory' : null;

        if ($this->lockedType === 'accessory') {
            $this->type = 'accessory';
        }

        if ($this->search === '') {
            $legacyKeyword = request()->query('keyword') ?? request()->query('q');
            if (is_string($legacyKeyword) && trim($legacyKeyword) !== '') {
                $this->search = trim($legacyKeyword);
            }
        }
    }

    public function updated(string $property): void
    {
        if ($this->lockedType === 'accessory') {
            $this->type = 'accessory';
        }

        if (in_array($property, ['search', 'type', 'categoryId', 'minPrice', 'maxPrice', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function selectType(string $value = ''): void
    {
        if ($this->lockedType === 'accessory') {
            return;
        }

        $this->type = $value;
        $this->categoryId = '';
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->categoryId = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->sort = 'latest';
        $this->type = $this->lockedType === 'accessory' ? 'accessory' : '';
        $this->resetPage();
    }

    public function render(VehicleSearchService $searchService, ShowroomCacheService $cacheService): View
    {
        $effectiveType = $this->lockedType === 'accessory' ? 'accessory' : $this->type;
        $isAccessoryCatalog = $effectiveType === 'accessory';

        $filters = array_filter([
            'search' => trim($this->search),
            'type' => $effectiveType,
            'category_id' => $this->categoryId,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'sort' => $this->sort !== '' ? $this->sort : 'latest',
        ], static fn (mixed $value): bool => $value !== '' && $value !== null);

        $vehicles = $searchService->search($filters);

        $categories = $cacheService->catalogCategories($isAccessoryCatalog);

        return view('livewire.catalog-browser', [
            'vehicles' => $vehicles,
            'categories' => $categories,
            'types' => VehicleType::cases(),
            'isAccessoryCatalog' => $isAccessoryCatalog,
            'filterAction' => $isAccessoryCatalog ? route('accessories.index') : route('products.index'),
        ]);
    }
}
