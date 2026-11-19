<?php

namespace App\Services\Cache;

use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Cache;

class ShowroomCacheService
{
    public const KEY_HOME_DATA = 'showroom:home:data';

    public const KEY_DASHBOARD_SUMMARY = 'showroom:dashboard:summary';

    public const KEY_CATALOG_VERSION = 'showroom:catalog:version';

    public const KEY_ARTICLES_VERSION = 'showroom:articles:version';

    public const KEY_CATEGORIES_VEHICLES = 'showroom:categories:vehicles';

    public const KEY_CATEGORIES_ACCESSORIES = 'showroom:categories:accessories';

    /**
     * Retrieve public homepage showcase datasets with stale-while-revalidate caching.
     *
     * @return array{
     *     featuredProducts: EloquentCollection<int, Product>,
     *     latestArticles: EloquentCollection<int, Article>,
     *     primaryCategories: EloquentCollection<int, Category>,
     *     publicFormBackground: string
     * }
     */
    public function homeData(): array
    {
        $key = self::KEY_HOME_DATA.':v'.$this->catalogVersion().':'.$this->articlesVersion();

        return Cache::flexible($key, [60, 300], static function (): array {
            $featuredProducts = Product::active()
                ->where('is_featured', true)
                ->with(['category', 'primaryImage', 'images'])
                ->latest()
                ->take(3)
                ->get();

            $latestArticles = Article::published()
                ->latest('published_at')
                ->take(3)
                ->get();

            $primaryCategories = Category::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'logo']);

            $publicFormBackground = SiteSetting::publicFormBackgroundImageUrl();

            return [
                'featuredProducts' => $featuredProducts,
                'latestArticles' => $latestArticles,
                'primaryCategories' => $primaryCategories,
                'publicFormBackground' => $publicFormBackground,
            ];
        });
    }

    /**
     * Retrieve vehicle or accessory filter categories with stale-while-revalidate caching.
     *
     * @return EloquentCollection<int, Category>
     */
    public function catalogCategories(bool $isAccessoryCatalog): EloquentCollection
    {
        $baseKey = $isAccessoryCatalog
            ? self::KEY_CATEGORIES_ACCESSORIES
            : self::KEY_CATEGORIES_VEHICLES;

        $key = $baseKey.':v'.$this->catalogVersion();

        return Cache::flexible($key, [60, 300], static function () use ($isAccessoryCatalog): EloquentCollection {
            return $isAccessoryCatalog
                ? Category::query()->where('name', 'like', '%Phụ kiện%')->orderBy('name')->get()
                : Category::query()->where('name', 'not like', '%Phụ kiện%')->orderBy('name')->get();
        });
    }

    /**
     * @return EloquentCollection<int, Category>
     */
    public function publicCatalogFilters(bool $isAccessoryCatalog): EloquentCollection
    {
        return $this->catalogCategories($isAccessoryCatalog);
    }

    /**
     * Retrieve or flexibly revalidate CRM Dashboard aggregates (short [30, 120] TTL).
     *
     * @template T of array
     *
     * @param  Closure(): T  $loader
     * @return T
     */
    public function dashboardSummary(Closure $loader): array
    {
        return Cache::flexible(self::KEY_DASHBOARD_SUMMARY, [30, 120], $loader);
    }

    /**
     * Cache a public article filter/category lookup with stale-while-revalidate caching.
     *
     * @template T
     *
     * @param  Closure(): T  $loader
     * @return T
     */
    public function rememberArticlesSegment(string $segment, Closure $loader): mixed
    {
        $key = 'showroom:articles:'.$segment.':v'.$this->articlesVersion();

        return Cache::flexible($key, [60, 300], $loader);
    }

    /**
     * Cache a public catalog lookup segment keyed by catalog epoch version.
     *
     * @template T
     *
     * @param  Closure(): T  $loader
     * @return T
     */
    public function rememberCatalogSegment(string $segment, Closure $loader): mixed
    {
        $key = 'showroom:catalog:'.$segment.':v'.$this->catalogVersion();

        return Cache::flexible($key, [60, 300], $loader);
    }

    public function catalogVersion(): int
    {
        return max(1, (int) Cache::get(self::KEY_CATALOG_VERSION, 1));
    }

    public function articlesVersion(): int
    {
        return max(1, (int) Cache::get(self::KEY_ARTICLES_VERSION, 1));
    }

    public function flushCatalog(): void
    {
        $this->forgetFlexible(self::KEY_HOME_DATA.':v'.$this->catalogVersion().':'.$this->articlesVersion());
        $this->forgetFlexible(self::KEY_CATEGORIES_VEHICLES.':v'.$this->catalogVersion());
        $this->forgetFlexible(self::KEY_CATEGORIES_ACCESSORIES.':v'.$this->catalogVersion());
        $this->forgetFlexible(self::KEY_HOME_DATA);
        $this->forgetFlexible(self::KEY_CATEGORIES_VEHICLES);
        $this->forgetFlexible(self::KEY_CATEGORIES_ACCESSORIES);

        Cache::put(self::KEY_CATALOG_VERSION, $this->catalogVersion() + 1, now()->addDays(30));
    }

    public function flushArticles(): void
    {
        $this->forgetFlexible(self::KEY_HOME_DATA.':v'.$this->catalogVersion().':'.$this->articlesVersion());
        $this->forgetFlexible(self::KEY_HOME_DATA);

        Cache::put(self::KEY_ARTICLES_VERSION, $this->articlesVersion() + 1, now()->addDays(30));
    }

    public function flushDashboard(): void
    {
        $this->forgetFlexible(self::KEY_DASHBOARD_SUMMARY);
    }

    /**
     * Forget both value and Laravel Cache::flexible metadata key across all drivers.
     */
    public function forgetFlexible(string $key): void
    {
        Cache::forget($key);
        Cache::forget("illuminate:cache:flexible:created:{$key}");
    }
}
