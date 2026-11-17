<?php

namespace App\Http\Controllers;

use App\Events\AccessoryOrderCreated;
use App\Http\Controllers\Ai\ShowroomAssistantController;
use App\Http\Requests\StoreAccessoryOrderRequest;
use App\Models\AccessoryOrder;
use App\Models\Product;
use App\Services\Admin\AdminNotificationDispatcher;
use App\Services\Ai\AiConversationTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Context;
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
        AiConversationTracker $tracker,
        AdminNotificationDispatcher $notificationDispatcher
    ): RedirectResponse {
        abort_unless((bool) $product->is_active && $product->canOrderAccessory(), 404);

        $data = $request->validated();

        if (empty($data['ai_visitor_id'])) {
            $cookieVisitorId = $request->cookie(ShowroomAssistantController::VISITOR_COOKIE_NAME);
            if (is_string($cookieVisitorId) && preg_match('/^[A-Za-z0-9._:-]{6,80}$/', trim($cookieVisitorId)) === 1) {
                $data['ai_visitor_id'] = trim($cookieVisitorId);
            }
        }

        if (! empty($data['ai_visitor_id'])) {
            Context::add('ai_visitor_id', $data['ai_visitor_id']);
        }

        $order = AccessoryOrder::create([
            ...$data,
            'product_id' => $product->id,
        ]);

        $tracker->linkCustomerTouchpoint($request, 'accessory_order', $order->id, $data['ai_visitor_id'] ?? null, [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone,
        ]);

        $notificationDispatcher->dispatchNewAccessoryOrder($order);

        event(new AccessoryOrderCreated($order));

        return redirect()
            ->route('accessory-orders.create', $product->slug)
            ->with('success', 'Đơn đặt hàng phụ kiện đã được ghi nhận. Showroom sẽ liên hệ xác nhận thủ công.');
    }
}
