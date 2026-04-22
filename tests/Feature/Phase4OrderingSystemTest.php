<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class Phase4OrderingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cartService = app(CartService::class);
    }

    /** @test */
    public function guest_can_add_item_to_session_cart()
    {
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->post(route('cart.store'), [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response->assertRedirect();
        $this->assertEquals(1, $this->cartService->getCount());

        $sessionCart = Session::get('guest_cart');
        $this->assertArrayHasKey($product->id, $sessionCart);
    }

    /** @test */
    public function session_cart_merges_to_database_on_login()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // 1. Khách thêm xe vào giỏ
        Session::put('guest_cart', [$product->id => 2]);

        // 2. Thực hiện đăng nhập
        $this->actingAs($user);

        // Trigger Login event (Laravel mock login doesn't fire event automatically in some setups, but Breeze does)
        event(new Login('web', $user, false));

        // 3. Kiểm tra DB cart
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertEquals(2, $cart->items()->where('product_id', $product->id)->first()->quantity);
        $this->assertEmpty(Session::get('guest_cart'));
    }

    /** @test */
    public function checkout_processes_deposit_and_locks_stock_atomically()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'stock' => 1,
            'deposit_amount' => 5000000,
            'name' => 'BMW M5',
        ]);

        $this->actingAs($user);
        $this->cartService->addItem($product->id, 1);

        $response = $this->post(route('checkout.store'));

        // Kiểm tra redirect tới trang success
        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));

        // Kiểm tra Stock đã trừ
        $this->assertEquals(0, $product->fresh()->stock);

        // Kiểm tra Order Status & Expiry
        $this->assertEquals(OrderStatus::PendingPayment, $order->status);
        $this->assertNotNull($order->expires_at);

        // KIỂM TRA SNAPSHOT INTEGRITY: Đảm bảo giá và tên đã được "chụp" lại
        $orderItem = $order->items()->first();
        $this->assertEquals('BMW M5', $orderItem->product_name);
        $this->assertEquals(5000000, $orderItem->deposit_amount_snapshot);

        // Giỏ hàng phải trống sau khi checkout
        $this->assertEquals(0, $this->cartService->getCount());
    }

    /** @test */
    public function deposit_fails_if_stock_is_insufficient()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $this->actingAs($user);
        $this->cartService->addItem($product->id, 1);

        $response = $this->post(route('checkout.store'));

        $response->assertRedirect(route('cart.index'));
        $response->assertSessionHas('error');

        // Stock vẫn là 0
        $this->assertEquals(0, $product->fresh()->stock);
        // Không có order nào được tạo
        $this->assertEquals(0, Order::count());
    }

    /** @test */
    public function expired_orders_release_stock_via_command()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        // 1. Tạo một đơn hàng đã hết hạn (25 giờ trước)
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PendingPayment,
            'expires_at' => now()->subHours(25),
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'deposit_amount_snapshot' => $product->deposit_amount,
            'quantity' => 1,
        ]);

        // 2. Chạy command xử lý hết hạn
        Artisan::call('orders:expire');

        // 3. Kiểm tra kết quả
        $this->assertEquals(OrderStatus::Expired, $order->fresh()->status);
        $this->assertEquals(1, $product->fresh()->stock); // Stock phải được hoàn trả
    }
}
