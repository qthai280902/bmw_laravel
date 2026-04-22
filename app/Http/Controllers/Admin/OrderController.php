<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConfirmOrderPaymentAction;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Danh sách đơn hàng.
     * Chống N+1 bằng Eager Loading: with('user').
     */
    public function index(Request $request)
    {
        $query = Order::with('user')->latest();

        // Lọc theo trạng thái
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    /**
     * Xem chi tiết đơn hàng.
     */
    public function show(Order $order)
    {
        $order->load(['user', 'items']);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Xác nhận thanh toán đơn hàng.
     */
    public function update(Order $order, ConfirmOrderPaymentAction $action)
    {
        try {
            $action->execute($order);

            return redirect()
                ->route('admin.orders.show', $order)
                ->with('success', "Đơn hàng #{$order->id} đã được xác nhận thanh toán thành công.");
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
