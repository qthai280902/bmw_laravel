<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

class ProductImageExpansionSeeder extends Seeder
{
    /**
     * @var array<int, string>
     */
    private const VEHICLE_IMAGE_FILES = [
        'hero-front-three-quarter.png',
        'side-profile.png',
        'rear-three-quarter.png',
        'cockpit-interior.png',
        'design-detail.png',
        'lifestyle-showroom.png',
    ];

    /**
     * @var array<int, string>
     */
    private const ACCESSORY_IMAGE_FILES = [
        'hero-product.png',
        'detail-material.png',
        'installed-context.png',
        'lifestyle-use.png',
        'compatibility-context.png',
        'studio-angle.png',
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private const PRODUCT_IMAGE_LIBRARY = [
        'bmw-330i-sedan' => [
            'paths' => [
                'images/cars/330i/hero-front-three-quarter.png',
                'images/cars/330i/side-profile.png',
                'images/cars/330i/rear-three-quarter.png',
                'images/cars/330i/cockpit-interior.png',
                'images/cars/330i/design-detail-wheel-light.png',
                'images/cars/330i/lifestyle-showroom.png',
                'images/cars/330i/urban-motion.png',
                'images/cars/330i/studio-front-three-quarter.png',
                'images/cars/330i.png',
            ],
        ],
        'bmw-530i-sedan' => ['group' => 'cars', 'legacy_path' => 'images/cars/530i.png'],
        'bmw-550e-xdrive-sedan' => ['group' => 'cars', 'legacy_path' => 'images/cars/550e.png'],
        'bmw-i4-m60-gran-coupe' => ['group' => 'cars', 'legacy_path' => 'images/cars/i4.png'],
        'bmw-x3-m50-xdrive' => ['group' => 'cars', 'legacy_path' => 'images/cars/x3m50.png'],
        'bmw-m3-sedan' => ['group' => 'cars', 'legacy_path' => 'images/cars/m3.png'],
        'bmw-m4-coupe' => ['group' => 'cars', 'legacy_path' => 'images/cars/m4.png'],
        'bmw-x5-m-competition' => ['group' => 'cars', 'legacy_path' => 'images/cars/x5m.png'],
        'bmw-xm-label' => ['group' => 'cars', 'legacy_path' => 'images/cars/xm.png'],
        'bmw-m5-touring' => ['group' => 'cars', 'legacy_path' => 'images/cars/m5t.png'],
        'bmw-g310r' => ['group' => 'motorbikes'],
        'bmw-s1000rr' => ['group' => 'motorbikes'],
        'bmw-r1250gs' => ['group' => 'motorbikes'],
        'bmw-f900r' => ['group' => 'motorbikes'],
        'bmw-k1600gt' => ['group' => 'motorbikes'],
        'tham-lot-san-m-performance' => ['group' => 'accessories'],
        'camera-hanh-trinh-bmw-advanced-eye-30' => ['group' => 'accessories'],
        'boc-vo-lang-m-performance-carbon' => ['group' => 'accessories'],
        'bo-vanh-bmw-m-performance-y-spoke-20' => ['group' => 'accessories'],
        'bo-den-led-noi-that-ambient-light-pro' => ['group' => 'accessories'],
        'thung-nhom-bmw-motorrad-vario-35l' => ['group' => 'accessories'],
        'mu-bao-hiem-bmw-system-7-carbon' => ['group' => 'accessories'],
        'op-carbon-bao-ve-dong-co-s1000rr' => ['group' => 'accessories'],
        'ao-giap-bmw-motorrad-streetguard' => ['group' => 'accessories'],
        'bo-bao-ve-gam-bmw-motorrad-enduro' => ['group' => 'accessories'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::PRODUCT_IMAGE_LIBRARY as $slug => $definition) {
            $product = Product::query()
                ->where('slug', $slug)
                ->first();

            if (! $product) {
                continue;
            }

            $this->removeRemoteSeedImages($product);
            $product->images()->update(['is_primary' => false]);

            foreach ($this->imagesFor($slug, $definition) as $sortOrder => $path) {
                $product->images()->updateOrCreate(
                    ['path' => $path],
                    [
                        'is_primary' => $sortOrder === 0,
                        'sort_order' => $sortOrder,
                    ]
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<int, string>
     */
    private function imagesFor(string $slug, array $definition): array
    {
        if (isset($definition['paths'])) {
            return $definition['paths'];
        }

        $group = (string) $definition['group'];
        $files = $group === 'accessories'
            ? self::ACCESSORY_IMAGE_FILES
            : self::VEHICLE_IMAGE_FILES;

        $paths = array_map(
            fn (string $file): string => "images/{$group}/{$slug}/{$file}",
            $files
        );

        if (isset($definition['legacy_path'])) {
            $paths[] = (string) $definition['legacy_path'];
        }

        return $paths;
    }

    private function removeRemoteSeedImages(Product $product): void
    {
        $product->images()
            ->where(function (Builder $query): void {
                $query->where('path', 'like', 'http://%')
                    ->orWhere('path', 'like', 'https://%');
            })
            ->delete();
    }
}
