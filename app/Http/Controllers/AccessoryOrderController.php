<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccessoryOrderRequest;
use App\Models\AccessoryOrder;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessoryOrderController extends Controller
{
    public function create(Product $product): View
    {
        abort_unless($product->canOrderAccessory(), 404);

        $product->loadMissing(['category', 'primaryImage', 'images']);

        return view('accessory-orders.create', [
            'product' => $product,
        ]);
    }

    public function store(StoreAccessoryOrderRequest $request, Product $product): RedirectResponse
    {
        abort_unless($product->canOrderAccessory(), 404);

        AccessoryOrder::create([
            ...$request->validated(),
            'product_id' => $product->id,
        ]);

        return redirect()
            ->route('accessory-orders.create', $product->slug)
            ->with('success', 'Đơn đặt hàng phụ kiện đã được ghi nhận. Showroom sẽ liên hệ xác nhận thủ công.');
    }
}
