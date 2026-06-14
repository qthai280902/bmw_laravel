<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\AccessoryOrder;
use App\Models\Article;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUiPhase14Test extends TestCase
{
    use RefreshDatabase;

    public function test_core_admin_pages_render_with_modernized_shell(): void
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
        $accessory = Product::factory()
            ->for($category)
            ->create([
                'type' => VehicleType::ACCESSORY,
                'name' => 'Thảm lót sàn BMW',
                'slug' => 'tham-lot-san-bmw',
            ]);
        $order = AccessoryOrder::create([
            'product_id' => $accessory->id,
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000000',
            'customer_address' => '1 Nguyen Hue, Quan 1',
            'quantity' => 1,
        ]);

        $routes = [
            route('dashboard'),
            route('admin.products.index'),
            route('admin.products.create'),
            route('admin.products.edit', $product),
            route('admin.categories.index'),
            route('admin.categories.create'),
            route('admin.categories.edit', $category),
            route('admin.appointments.index'),
            route('admin.accessory-orders.index'),
            route('admin.accessory-orders.show', $order),
            route('admin.articles.index'),
            route('admin.articles.create'),
        ];

        foreach ($routes as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertSee('BMW Admin')
                ->assertSee('Bài viết')
                ->assertSee(route('admin.articles.index', absolute: false), false)
                ->assertDontSee('href="#"', false);
        }
    }

    public function test_admin_article_edit_uses_delete_modal_contract_on_index(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $article = Article::factory()->published()->create([
            'title' => 'Tin tức showroom BMW',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.articles.index'))
            ->assertOk()
            ->assertSee($article->title)
            ->assertSee('admin-delete-form', false)
            ->assertSee('admin-delete-confirm-modal', false)
            ->assertDontSee('confirm(', false);
    }
}
