<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase16_1AiWidgetCompareTest extends TestCase
{
    use RefreshDatabase;

    public function test_compare_normalizes_vehicle_ids_preserves_order_and_ignores_invalid_duplicates_and_accessories(): void
    {
        $category = Category::factory()->create([
            'name' => 'Sedan',
            'slug' => 'sedan',
        ]);

        $bmw330i = $this->createProduct($category, 'BMW 330i Sedan', 'bmw-330i-sedan', VehicleType::CAR, '258 hp');
        $bmw530i = $this->createProduct($category, 'BMW 530i Sedan', 'bmw-530i-sedan', VehicleType::CAR, '252 hp');
        $bmwS1000rr = $this->createProduct($category, 'BMW S1000RR', 'bmw-s1000rr', VehicleType::MOTORBIKE, '205 hp');
        $bmwX5 = $this->createProduct($category, 'BMW X5 M Competition', 'bmw-x5-m-competition', VehicleType::CAR, '617 hp');
        $bmwM5 = $this->createProduct($category, 'BMW M5 Touring', 'bmw-m5-touring', VehicleType::CAR, '717 hp');
        $accessory = $this->createProduct($category, 'Thảm lót sàn M Performance', 'tham-lot-san-m-performance', VehicleType::ACCESSORY, null);

        $this->get(route('products.compare', [
            'ids' => implode(',', [
                $bmw530i->id,
                $bmw330i->id,
                $bmw330i->id,
                'abc',
                $accessory->id,
                $bmwS1000rr->id,
                $bmwX5->id,
                $bmwM5->id,
            ]),
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                $bmw530i->name,
                $bmw330i->name,
                $bmwS1000rr->name,
                $bmwX5->name,
            ])
            ->assertSeeInOrder(['252 hp', '258 hp', '205 hp', '617 hp'])
            ->assertDontSee($accessory->name)
            ->assertDontSee($bmwM5->name);
    }

    public function test_compare_handles_empty_and_invalid_ids_without_error(): void
    {
        $this->get(route('products.compare'))
            ->assertOk()
            ->assertSee('Chọn sản phẩm để so sánh');

        $this->get(route('products.compare', ['ids' => 'abc,999999']))
            ->assertOk()
            ->assertSee('Chọn sản phẩm để so sánh');
    }

    private function createProduct(
        Category $category,
        string $name,
        string $slug,
        VehicleType $type,
        ?string $horsepower
    ): Product {
        return Product::factory()
            ->for($category)
            ->create([
                'name' => $name,
                'slug' => $slug,
                'type' => $type,
                'is_active' => true,
                'specifications' => $horsepower ? ['Horsepower' => $horsepower] : [],
            ]);
    }
}
