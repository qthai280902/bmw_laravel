<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\AccessoryOrder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessoryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_accessory_order_form_can_be_rendered(): void
    {
        $accessory = $this->createAccessory();

        $this->get(route('accessory-orders.create', $accessory->slug))
            ->assertOk()
            ->assertSee('Đặt hàng phụ kiện')
            ->assertSee($accessory->name)
            ->assertSee('name="customer_name"', false)
            ->assertSee('name="customer_address"', false)
            ->assertSee('name="customer_phone"', false)
            ->assertSee('name="quantity"', false)
            ->assertDontSee('test_drive', false);
    }

    public function test_accessory_order_can_be_submitted(): void
    {
        $accessory = $this->createAccessory();

        $response = $this->post(route('accessory-orders.store', $accessory->slug), [
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000000',
            'customer_address' => '1 Nguyen Hue, Quan 1',
            'customer_email' => 'customer@example.com',
            'quantity' => 2,
            'notes' => 'Can tu van lap dat.',
        ]);

        $response
            ->assertRedirect(route('accessory-orders.create', $accessory->slug))
            ->assertSessionHas('success');

        $this->assertDatabaseHas(AccessoryOrder::class, [
            'product_id' => $accessory->id,
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000000',
            'customer_address' => '1 Nguyen Hue, Quan 1',
            'customer_email' => 'customer@example.com',
            'quantity' => 2,
            'status' => AccessoryOrder::STATUS_PENDING,
        ]);
    }

    public function test_accessory_order_validates_required_customer_fields(): void
    {
        $accessory = $this->createAccessory();

        $this->post(route('accessory-orders.store', $accessory->slug), [
            'quantity' => 0,
        ])->assertSessionHasErrors([
            'customer_name',
            'customer_phone',
            'customer_address',
            'quantity',
        ]);
    }

    public function test_vehicle_products_cannot_use_accessory_order_flow(): void
    {
        $vehicle = $this->createVehicle();

        $this->get(route('accessory-orders.create', $vehicle->slug))
            ->assertNotFound();

        $this->post(route('accessory-orders.store', $vehicle->slug), [
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000000',
            'customer_address' => '1 Nguyen Hue, Quan 1',
            'quantity' => 1,
        ])->assertNotFound();
    }

    public function test_accessory_detail_uses_order_cta_without_test_drive_or_compare(): void
    {
        $accessory = $this->createAccessory();

        $this->get(route('products.show', $accessory->slug))
            ->assertOk()
            ->assertSee(route('accessory-orders.create', $accessory->slug, absolute: false), false)
            ->assertSee('Đặt hàng ngay')
            ->assertDontSee('type=test_drive', false)
            ->assertDontSee('toggleComparison('.$accessory->id, false);
    }

    public function test_compare_flow_ignores_accessory_ids(): void
    {
        $vehicle = $this->createVehicle();
        $accessory = $this->createAccessory();

        $this->get(route('products.compare', [
            'ids' => $vehicle->id.','.$accessory->id,
        ]))
            ->assertOk()
            ->assertSee($vehicle->name)
            ->assertDontSee($accessory->name);
    }

    private function createAccessory(): Product
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện Ô tô',
            'slug' => 'phu-kien-o-to',
        ]);

        return Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW Travel Comfort System',
                'slug' => 'bmw-travel-comfort-system',
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
                'price' => 12000000,
            ]);
    }

    private function createVehicle(): Product
    {
        $category = Category::factory()->create([
            'name' => 'Sedan',
            'slug' => 'sedan',
        ]);

        return Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW 330i Sedan',
                'slug' => 'bmw-330i-sedan',
                'type' => VehicleType::CAR,
                'is_active' => true,
            ]);
    }
}
