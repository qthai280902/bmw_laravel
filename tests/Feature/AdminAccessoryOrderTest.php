<?php

namespace Tests\Feature;

use App\Enums\VehicleType;
use App\Models\AccessoryOrder;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessoryOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_accessory_orders(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $order = $this->createAccessoryOrder();

        $this->actingAs($admin)
            ->get(route('admin.accessory-orders.index'))
            ->assertOk()
            ->assertSee($order->customer_name)
            ->assertSee($order->customer_phone)
            ->assertSee($order->product->name);
    }

    public function test_admin_can_view_accessory_order_detail(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $order = $this->createAccessoryOrder();

        $this->actingAs($admin)
            ->get(route('admin.accessory-orders.show', $order))
            ->assertOk()
            ->assertSee($order->customer_address)
            ->assertSee($order->product->name)
            ->assertSee('Ghi chú nội bộ');
    }

    public function test_admin_can_update_status_and_internal_notes(): void
    {
        $admin = User::factory()->create(['email' => 'admin@bmw.com']);
        $order = $this->createAccessoryOrder();

        $this->actingAs($admin)
            ->patch(route('admin.accessory-orders.update-status', $order), [
                'status' => AccessoryOrder::STATUS_CONFIRMED,
                'admin_notes' => 'Da goi xac nhan voi khach.',
            ])
            ->assertRedirect(route('admin.accessory-orders.show', $order));

        $order->refresh();

        $this->assertSame(AccessoryOrder::STATUS_CONFIRMED, $order->status);
        $this->assertSame('Da goi xac nhan voi khach.', $order->admin_notes);
        $this->assertNotNull($order->confirmed_at);
    }

    public function test_non_admin_cannot_manage_accessory_orders(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->actingAs($user)
            ->get(route('admin.accessory-orders.index'))
            ->assertForbidden();
    }

    private function createAccessoryOrder(): AccessoryOrder
    {
        $category = Category::factory()->create([
            'name' => 'Phụ kiện Ô tô',
            'slug' => 'phu-kien-o-to',
        ]);

        $product = Product::factory()
            ->for($category)
            ->create([
                'name' => 'BMW Travel Comfort System',
                'slug' => 'bmw-travel-comfort-system',
                'type' => VehicleType::ACCESSORY,
                'is_active' => true,
            ]);

        return AccessoryOrder::create([
            'product_id' => $product->id,
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0909000000',
            'customer_address' => '1 Nguyen Hue, Quan 1',
            'customer_email' => 'customer@example.com',
            'quantity' => 2,
            'notes' => 'Can tu van lap dat.',
        ]);
    }
}
