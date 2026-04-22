<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    use AuthorizesRequests;

    /**
     * Dashboard: Hiển thị lịch sử đơn hàng của khách.
     */
    public function index()
    {
        $orders = Auth::user()
            ->orders()
            ->with(['items.product' => function ($query) {
                $query->withTrashed();
            }])
            ->latest()
            ->paginate(10);

        return view('dashboard', compact('orders'));
    }

    /**
     * Chi tiết đơn hàng cho khách.
     */
    public function show(Order $order)
    {
        // Kiểm tra quyền sở hữu bằng Policy
        $this->authorize('view', $order);

        $order->load(['items.product' => function ($query) {
            $query->withTrashed();
        }]);

        return view('client.orders.show', compact('order'));
    }
}
