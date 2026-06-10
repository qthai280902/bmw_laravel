<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicUiPhase12Test extends TestCase
{
    use RefreshDatabase;

    public function test_compare_page_uses_primary_or_first_product_image_without_external_placeholder(): void
    {
        $category = Category::factory()->create([
            'name' => 'Sedan',
            'slug' => 'sedan',
        ]);

        $primaryProduct = Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW 330i Sedan',
                'slug' => 'bmw-330i-sedan',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);

        ProductImage::create([
            'product_id' => $primaryProduct->id,
            'path' => 'images/cars/330i.png',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $galleryFallbackProduct = Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW 530i Sedan',
                'slug' => 'bmw-530i-sedan',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);

        ProductImage::create([
            'product_id' => $galleryFallbackProduct->id,
            'path' => 'images/cars/530i.png',
            'is_primary' => false,
            'sort_order' => 0,
        ]);

        $this->get(route('products.compare', [
            'ids' => $primaryProduct->id.','.$galleryFallbackProduct->id,
        ]))
            ->assertOk()
            ->assertSee('/images/cars/330i.png', false)
            ->assertSee('/images/cars/530i.png', false)
            ->assertDontSee('placehold.co', false)
            ->assertDontSee('/storage/images/cars/330i.png', false);
    }

    public function test_vehicle_detail_keeps_test_drive_quote_compare_and_specs_modal(): void
    {
        $category = Category::factory()->create([
            'name' => 'M Performance',
            'slug' => 'm-performance',
        ]);

        $vehicle = Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW M3 Sedan',
                'slug' => 'bmw-m3-sedan',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);

        ProductImage::create([
            'product_id' => $vehicle->id,
            'path' => 'images/cars/m3.png',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('products.show', $vehicle->slug))
            ->assertOk()
            ->assertSee('type=test_drive', false)
            ->assertSee('type=quote', false)
            ->assertSee('Thêm so sánh')
            ->assertSee('showDetailedSpecs', false)
            ->assertSee('technical-data', false);
    }

    public function test_accessory_detail_removes_test_drive_and_uses_accessory_ctas(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện Ô tô',
            'slug' => 'phu-kien-o-to',
        ]);

        $accessory = Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW Travel Comfort System',
                'slug' => 'bmw-travel-comfort-system',
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
            ]);

        ProductImage::create([
            'product_id' => $accessory->id,
            'path' => 'images/cars/x3m50.png',
            'is_primary' => true,
            'sort_order' => 0,
        ]);

        $this->get(route('products.show', $accessory->slug))
            ->assertOk()
            ->assertSee('Đặt hàng ngay')
            ->assertSee('Liên hệ mua hàng')
            ->assertSee('type=quote', false)
            ->assertSee('type=consult', false)
            ->assertDontSee('Đăng ký lái thử')
            ->assertDontSee('type=test_drive', false)
            ->assertDontSee('Thêm so sánh');
    }
}
