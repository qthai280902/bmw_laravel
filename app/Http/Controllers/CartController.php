<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddToCartRequest;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(): View
    {
        $items = $this->cartService->getItems();
        $total = $items->sum(fn ($item) => ($item->product?->deposit_amount ?? 0) * $item->quantity);

        return view('cart.index', compact('items', 'total'));
    }

    public function store(AddToCartRequest $request): RedirectResponse
    {
        $this->cartService->addItem(
            productId: $request->integer('product_id'),
            quantity: $request->integer('quantity', 1),
        );

        return back()->with('success', 'Đã thêm xe vào giỏ hàng.');
    }

    public function destroy(int $productId): RedirectResponse
    {
        $this->cartService->removeItem($productId);

        return back()->with('success', 'Đã xóa xe khỏi giỏ hàng.');
    }

    public function clear(): RedirectResponse
    {
        $this->cartService->clearCart();

        return redirect()->route('cart.index')->with('success', 'Đã xóa toàn bộ giỏ hàng.');
    }
}
