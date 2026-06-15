<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductPreviewLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_product_edit_and_index_show_public_landing_preview_links(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $category = Category::factory()->create([
            'name' => 'BMW Sedan',
            'slug' => 'bmw-sedan',
        ]);
        $product = Product::factory()
            ->for($category)
            ->create([
                'type' => VehicleType::CAR,
                'name' => 'BMW 330i Sedan',
                'slug' => 'bmw-330i-sedan',
            ]);

        $publicPath = route('products.show', $product->slug, absolute: false);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('Xem trang public')
            ->assertSee($publicPath, false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener"', false);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Public')
            ->assertSee($publicPath, false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener"', false);
    }
}
