<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientOrderSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;

    protected User $userB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userA = User::factory()->create();
        $this->userB = User::factory()->create();
    }

    /** @test */
    public function user_can_only_see_their_own_orders_on_dashboard()
    {
        // User A has 1 order
        Order::factory()->create(['user_id' => $this->userA->id]);
        // User B has 1 order
        Order::factory()->create(['user_id' => $this->userB->id]);

        $response = $this->actingAs($this->userA)->get(route('dashboard'));

        $response->assertStatus(200);
        $this->assertCount(1, $response->viewData('orders'));
        $this->assertEquals($this->userA->id, $response->viewData('orders')->first()->user_id);
    }

    /** @test */
    public function user_cannot_view_another_users_order_detail()
    {
        // Order belongs to User B
        $orderB = Order::factory()->create(['user_id' => $this->userB->id]);

        // User A tries to view Order B
        $response = $this->actingAs($this->userA)->get(route('orders.show', $orderB));

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_their_own_order_detail()
    {
        $orderA = Order::factory()->create(['user_id' => $this->userA->id]);

        $response = $this->actingAs($this->userA)->get(route('orders.show', $orderA));

        $response->assertStatus(200);
    }

    /** @test */
    public function order_detail_displays_snapshot_even_if_product_is_soft_deleted()
    {
        $product = Product::factory()->create(['name' => 'BMW M3 ORIGINAL']);

        $order = Order::factory()->create(['user_id' => $this->userA->id]);
        $item = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => 'BMW M3 SNAPSHOT',
            'deposit_amount_snapshot' => 50000000,
        ]);

        // Product is soft deleted
        $product->delete();

        $response = $this->actingAs($this->userA)->get(route('orders.show', $order));

        $response->assertStatus(200);
        $response->assertSee('BMW M3 SNAPSHOT');
        $response->assertSee(number_format(50000000));
        // Verify we used withTrashed to still get relationships if needed
        $this->assertNotNull($response->viewData('order')->items->first()->product);
        $this->assertTrue($response->viewData('order')->items->first()->product->trashed());
    }
}
