<?php

namespace App\Http\Controllers;

use App\Actions\ProcessDepositAction;
use App\Exceptions\InsufficientStockException;
use App\Models\Cart;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected ProcessDepositAction $processDepositAction,
    ) {}

    public function index(): View|RedirectResponse
    {
        $items = $this->cartService->getItems();

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        $total = $items->sum(fn ($item) => ($item->product?->deposit_amount ?? 0) * $item->quantity);

        return view('checkout.index', compact('items', 'total'));
    }

    public function store(): RedirectResponse
    {
        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->with('items')->firstOrFail();

        if ($cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng của bạn đang trống.');
        }

        try {
            $order = $this->processDepositAction->execute($user, $cart);

            return redirect()->route('checkout.success', $order)
                ->with('success', 'Đặt cọc thành công! Vui lòng chuyển khoản trong 24 giờ.');
        } catch (InsufficientStockException $e) {
            return redirect()->route('cart.index')
                ->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('cart.index')
                ->with('error', 'Có lỗi xảy ra. Vui lòng thử lại.');
        }
    }

    public function success(Order $order): View
    {
        abort_if($order->user_id !== Auth::id(), 403);

        return view('checkout.success', compact('order'));
    }
}
