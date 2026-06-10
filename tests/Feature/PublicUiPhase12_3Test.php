<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductImageExpansionSeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUiPhase12_3Test extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, class-string>
     */
    private array $catalogSeeders = [
        CategorySeeder::class,
        ProductSeeder::class,
        ProductImageExpansionSeeder::class,
    ];

    public function test_product_image_expansion_covers_every_seeded_product_with_local_images(): void
    {
        $this->seed($this->catalogSeeders);

        $products = Product::query()
            ->with(['category', 'images'])
            ->orderBy('id')
            ->get();

        $this->assertCount(25, $products);

        foreach ($products as $product) {
            $minimumImageCount = $product->isAccessory() ? 6 : 6;
            $this->assertGreaterThanOrEqual(
                $minimumImageCount,
                $product->images->count(),
                "{$product->slug} should have enough product images."
            );

            $this->assertSame(
                1,
                $product->images->where('is_primary', true)->count(),
                "{$product->slug} should have exactly one primary image."
            );

            $this->assertSame(
                [],
                $product->images->groupBy('path')->filter(fn ($images) => $images->count() > 1)->keys()->all(),
                "{$product->slug} should not have duplicate image paths."
            );

            $this->assertSame(
                [],
                $product->images->groupBy('sort_order')->filter(fn ($images) => $images->count() > 1)->keys()->all(),
                "{$product->slug} should not have duplicate sort_order values."
            );

            foreach ($product->images as $image) {
                $this->assertFalse(
                    str_starts_with($image->path, 'http://') || str_starts_with($image->path, 'https://'),
                    "{$product->slug} should use local image paths."
                );

                $this->assertFileExists(public_path($image->path));
            }
        }
    }

    public function test_product_image_expansion_seeder_is_idempotent(): void
    {
        $this->seed($this->catalogSeeders);

        $before = $this->imageStateBySlug();

        $this->seed(ProductImageExpansionSeeder::class);

        $this->assertSame($before, $this->imageStateBySlug());
    }

    public function test_detail_catalog_accessories_and_compare_render_expanded_images(): void
    {
        $this->seed($this->catalogSeeders);

        $this->get(route('products.show', 'bmw-530i-sedan'))
            ->assertOk()
            ->assertSee('images/cars/bmw-530i-sedan/hero-front-three-quarter.png', false)
            ->assertSee('images/cars/bmw-530i-sedan/side-profile.png', false)
            ->assertSee('images/cars/bmw-530i-sedan/cockpit-interior.png', false)
            ->assertSee('type=test_drive', false)
            ->assertSee('type=quote', false);

        $this->get(route('products.show', 'bmw-s1000rr'))
            ->assertOk()
            ->assertSee('images/motorbikes/bmw-s1000rr/hero-front-three-quarter.png', false)
            ->assertSee('images/motorbikes/bmw-s1000rr/side-profile.png', false)
            ->assertDontSee('images/cars/330i/', false);

        $this->get(route('products.show', 'mu-bao-hiem-bmw-system-7-carbon'))
            ->assertOk()
            ->assertSee('images/accessories/mu-bao-hiem-bmw-system-7-carbon/hero-product.png', false)
            ->assertSee('images/accessories/mu-bao-hiem-bmw-system-7-carbon/detail-material.png', false)
            ->assertSee('type=quote', false)
            ->assertSee('type=consult', false)
            ->assertDontSee('type=test_drive', false);

        $this->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('https://images.unsplash.com', false)
            ->assertDontSee('https://upload.wikimedia.org', false);

        $this->get(route('products.index', ['type' => 'car']))
            ->assertOk()
            ->assertSee('images/cars/bmw-m5-touring/hero-front-three-quarter.png', false)
            ->assertDontSee('https://images.unsplash.com', false)
            ->assertDontSee('https://upload.wikimedia.org', false);

        $this->get(route('products.index', ['type' => 'motorbike']))
            ->assertOk()
            ->assertSee('images/motorbikes/bmw-g310r/hero-front-three-quarter.png', false)
            ->assertDontSee('https://upload.wikimedia.org', false);

        $this->get(route('accessories.index'))
            ->assertOk()
            ->assertSee('images/accessories/tham-lot-san-m-performance/hero-product.png', false)
            ->assertDontSee('https://images.unsplash.com', false);

        $this->get(route('products.compare', ['ids' => '2,3']))
            ->assertOk()
            ->assertSee('images/cars/bmw-530i-sedan/hero-front-three-quarter.png', false)
            ->assertSee('images/cars/bmw-550e-xdrive-sedan/hero-front-three-quarter.png', false)
            ->assertDontSee('placehold.co', false);
    }

    /**
     * @return array<string, array<int, array{path: string, is_primary: bool, sort_order: int}>>
     */
    private function imageStateBySlug(): array
    {
        return Product::query()
            ->with(['images' => fn ($query) => $query->orderBy('sort_order')->orderBy('path')])
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(fn (Product $product): array => [
                $product->slug => $product->images
                    ->map(fn ($image): array => [
                        'path' => $image->path,
                        'is_primary' => $image->is_primary,
                        'sort_order' => $image->sort_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }
}
