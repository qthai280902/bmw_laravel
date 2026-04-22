<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderPaymentConfirmed;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AdminOrderManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Giả lập Admin user
        $this->admin = User::factory()->create();
    }

    /** @test */
    public function admin_can_view_order_list_with_eager_loading()
    {
        $order = Order::factory()->create(['user_id' => $this->admin->id]);

        $response = $this->actingAs($this->admin)->get(route('admin.orders.index'));

        $response->assertStatus(200);
        $response->assertSee('#'.$order->id);
    }

    /** @test */
    public function admin_can_confirm_payment_only_for_pending_orders()
    {
        Event::fake();

        $order = Order::factory()->create([
            'status' => OrderStatus::PendingPayment,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $this->assertEquals(OrderStatus::Paid, $order->fresh()->status);

        Event::assertDispatched(OrderPaymentConfirmed::class);
    }

    /** @test */
    public function admin_cannot_confirm_payment_if_status_is_not_pending()
    {
        $order = Order::factory()->create([
            'status' => OrderStatus::Paid,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.orders.update', $order));

        $response->assertSessionHas('error');
        $this->assertEquals(OrderStatus::Paid, $order->fresh()->status);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_admin_orders()
    {
        $response = $this->get(route('admin.orders.index'));

        $response->assertRedirect(route('login'));
    }
}
