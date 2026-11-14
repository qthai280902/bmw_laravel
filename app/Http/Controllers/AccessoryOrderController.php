<?php

namespace App\Http\Controllers;

use App\Events\AccessoryOrderCreated;
use App\Http\Requests\StoreAccessoryOrderRequest;
use App\Models\AccessoryOrder;
use App\Models\Product;
use App\Services\Ai\AiConversationTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessoryOrderController extends Controller
{
    public function create(Product $product): View
    {
        abort_unless((bool) $product->is_active && $product->canOrderAccessory(), 404);

        $product->loadMissing(['category', 'primaryImage', 'images']);

        return view('accessory-orders.create', [
            'product' => $product,
        ]);
    }

    public function store(
        StoreAccessoryOrderRequest $request,
        Product $product,
        AiConversationTracker $tracker
    ): RedirectResponse {
        abort_unless((bool) $product->is_active && $product->canOrderAccessory(), 404);

        $data = $request->validated();

        $order = AccessoryOrder::create([
            ...$data,
            'product_id' => $product->id,
        ]);

        $tracker->linkCustomerTouchpoint($request, 'accessory_order', $order->id, $data['ai_visitor_id'] ?? null, [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone,
        ]);

        event(new AccessoryOrderCreated($order));

        return redirect()
            ->route('accessory-orders.create', $product->slug)
            ->with('success', 'Đơn đặt hàng phụ kiện đã được ghi nhận. Showroom sẽ liên hệ xác nhận thủ công.');
    }
}
