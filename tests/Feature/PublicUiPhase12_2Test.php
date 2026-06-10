<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\Bmw330iImageSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ProductSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUiPhase12_2Test extends TestCase
{
    use RefreshDatabase;

    public function test_bmw_330i_image_seeder_adds_a_usable_detail_image_set(): void
    {
        $this->seed([
            CategorySeeder::class,
            ProductSeeder::class,
            Bmw330iImageSeeder::class,
        ]);

        $product = Product::query()
            ->where('slug', 'bmw-330i-sedan')
            ->with(['primaryImage', 'images'])
            ->firstOrFail();

        $this->assertSame('images/cars/330i/hero-front-three-quarter.png', $product->primaryImage?->path);
        $this->assertGreaterThanOrEqual(8, $product->images()->count());

        foreach ($product->images as $image) {
            if (! str_starts_with($image->path, 'http')) {
                $this->assertFileExists(public_path($image->path));
            }
        }
    }

    public function test_bmw_330i_detail_renders_multiple_distinct_section_images(): void
    {
        $this->seed([
            CategorySeeder::class,
            ProductSeeder::class,
            Bmw330iImageSeeder::class,
        ]);

        $this->get(route('products.show', 'bmw-330i-sedan'))
            ->assertOk()
            ->assertSee('images/cars/330i/hero-front-three-quarter.png', false)
            ->assertSee('images/cars/330i/side-profile.png', false)
            ->assertSee('images/cars/330i/cockpit-interior.png', false)
            ->assertSee('images/cars/330i/design-detail-wheel-light.png', false)
            ->assertSee('images/cars/330i/lifestyle-showroom.png', false)
            ->assertSee('type=test_drive', false)
            ->assertSee('type=quote', false)
            ->assertSee('toggleComparison(1)', false)
            ->assertDontSee('placehold.co', false);
    }

    public function test_compare_still_uses_the_330i_primary_image_after_expansion(): void
    {
        $this->seed([
            CategorySeeder::class,
            ProductSeeder::class,
            Bmw330iImageSeeder::class,
        ]);

        $this->get(route('products.compare', ['ids' => '1,2']))
            ->assertOk()
            ->assertSee('images/cars/330i/hero-front-three-quarter.png', false)
            ->assertSee('BMW 330i Sedan')
            ->assertSee('BMW 530i Sedan');
    }
}
