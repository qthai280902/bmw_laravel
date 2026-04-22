<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    private const SESSION_KEY = 'guest_cart'; // [product_id => quantity]

    /**
     * Lấy hoặc tạo Cart cho User đang đăng nhập.
     */
    public function getOrCreateDatabaseCart(User $user): Cart
    {
        return Cart::firstOrCreate(['user_id' => $user->id]);
    }

    /**
     * Lấy Cart hiện tại: Database (User) hoặc Session (Guest).
     */
    public function getCart(): Cart|array
    {
        if (Auth::check()) {
            return $this->getOrCreateDatabaseCart(Auth::user());
        }

        return Session::get(self::SESSION_KEY, []);
    }

    /**
     * Lấy danh sách items, chuẩn hóa tương thích cả 2 chế độ.
     *
     * @return Collection<int, object>
     */
    public function getItems(): Collection
    {
        if (Auth::check()) {
            $cart = $this->getOrCreateDatabaseCart(Auth::user());

            return $cart->items()->with('product.brand')->get();
        }

        $sessionCart = Session::get(self::SESSION_KEY, []);

        if (empty($sessionCart)) {
            return collect();
        }

        $productIds = array_keys($sessionCart);
        $products = Product::with('brand', 'primaryImage')
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        return collect($sessionCart)->map(function (int $quantity, int $productId) use ($products) {
            return (object) [
                'product_id' => $productId,
                'quantity' => $quantity,
                'product' => $products->get($productId),
            ];
        })->filter(fn ($item) => $item->product !== null)->values();
    }

    /**
     * Thêm sản phẩm vào giỏ hàng.
     */
    public function addItem(int $productId, int $quantity = 1): void
    {
        if (Auth::check()) {
            $cart = $this->getOrCreateDatabaseCart(Auth::user());
            $item = $cart->items()->where('product_id', $productId)->first();

            if ($item instanceof CartItem) {
                $item->increment('quantity', $quantity);
            } else {
                $cart->items()->create([
                    'product_id' => $productId,
                    'quantity' => $quantity,
                ]);
            }

            return;
        }

        $cart = Session::get(self::SESSION_KEY, []);
        $cart[$productId] = ($cart[$productId] ?? 0) + $quantity;
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Xóa một sản phẩm khỏi giỏ hàng.
     */
    public function removeItem(int $productId): void
    {
        if (Auth::check()) {
            $cart = $this->getOrCreateDatabaseCart(Auth::user());
            $cart->items()->where('product_id', $productId)->delete();

            return;
        }

        $cart = Session::get(self::SESSION_KEY, []);
        unset($cart[$productId]);
        Session::put(self::SESSION_KEY, $cart);
    }

    /**
     * Xóa toàn bộ giỏ hàng.
     */
    public function clearCart(): void
    {
        if (Auth::check()) {
            $cart = $this->getOrCreateDatabaseCart(Auth::user());
            $cart->items()->delete();

            return;
        }

        Session::forget(self::SESSION_KEY);
    }

    /**
     * Đếm tổng số items trong giỏ hàng (dùng cho navbar badge).
     */
    public function getCount(): int
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();

            return $cart ? $cart->items()->sum('quantity') : 0;
        }

        return (int) collect(Session::get(self::SESSION_KEY, []))->sum();
    }

    /**
     * Merge giỏ hàng Session vào Database sau khi User đăng nhập.
     * Được gọi từ MergeSessionCartOnLogin listener.
     */
    public function mergeSessionCartToDatabase(User $user): void
    {
        $sessionCart = Session::get(self::SESSION_KEY, []);

        if (empty($sessionCart)) {
            return;
        }

        $dbCart = $this->getOrCreateDatabaseCart($user);

        foreach ($sessionCart as $productId => $quantity) {
            /** @var CartItem|null $existingItem */
            $existingItem = $dbCart->items()->where('product_id', $productId)->first();

            if ($existingItem instanceof CartItem) {
                $existingItem->increment('quantity', $quantity);
            } else {
                $dbCart->items()->create([
                    'product_id' => (int) $productId,
                    'quantity' => $quantity,
                ]);
            }
        }

        Session::forget(self::SESSION_KEY);
    }
}
