<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProcessDepositAction
{
    /**
     * Thực hiện đặt cọc với Stock Locking an toàn.
     *
     * DEADLOCK PREVENTION: Items được sắp xếp theo product_id TRƯỚC KHI lock.
     * Điều này đảm bảo MySQL luôn acquire lock theo cùng thứ tự giữa các transaction,
     * loại bỏ hoàn toàn circular lock dependency.
     *
     * @throws InsufficientStockException
     * @throws \Throwable
     */
    public function execute(User $user, Cart $cart): Order
    {
        return DB::transaction(function () use ($user, $cart) {
            // DEADLOCK PREVENTION: Sort items by product_id ascending before locking.
            // This guarantees a consistent lock acquisition order across concurrent transactions.
            $sortedItems = $cart->items()->with('product')->get()->sortBy('product_id');

            $totalDeposit = 0;
            $lockedProducts = [];

            // Phase 1: Acquire locks and validate stock
            foreach ($sortedItems as $item) {
                /** @var Product $product */
                $product = Product::lockForUpdate()->findOrFail($item->product_id);

                if ($product->stock < $item->quantity) {
                    throw new InsufficientStockException($product, $item->quantity);
                }

                $lockedProducts[$item->product_id] = [
                    'product' => $product,
                    'quantity' => $item->quantity,
                ];

                $totalDeposit += $product->deposit_amount * $item->quantity;
            }

            // Phase 2: Deduct stock (all checks passed)
            foreach ($lockedProducts as $data) {
                $data['product']->decrement('stock', $data['quantity']);
            }

            // Phase 3: Create order with price snapshot
            $order = Order::create([
                'user_id' => $user->id,
                'status' => OrderStatus::PendingPayment,
                'total_deposit_amount' => $totalDeposit,
                'expires_at' => now()->addHours(24),
            ]);

            foreach ($lockedProducts as $productId => $data) {
                $product = $data['product'];

                $order->items()->create([
                    'product_id' => $productId,
                    'product_name' => $product->name,         // Snapshot
                    'deposit_amount_snapshot' => $product->deposit_amount, // Snapshot
                    'quantity' => $data['quantity'],
                ]);
            }

            // Phase 4: Clear the cart
            $cart->items()->delete();

            return $order;
        });
    }
}
