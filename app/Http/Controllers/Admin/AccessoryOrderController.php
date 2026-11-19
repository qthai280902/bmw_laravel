<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccessoryOrderStatusRequest;
use App\Models\AccessoryOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccessoryOrderController extends Controller
{
    public function index(): View
    {
        return view('admin.accessory-orders.index');
    }

    public function show(AccessoryOrder $accessoryOrder): View
    {
        $accessoryOrder->loadMissing(['product.category', 'product.primaryImage', 'product.images']);

        return view('admin.accessory-orders.show', [
            'order' => $accessoryOrder,
            'statusOptions' => AccessoryOrder::statuses(),
        ]);
    }

    public function updateStatus(
        UpdateAccessoryOrderStatusRequest $request,
        AccessoryOrder $accessoryOrder
    ): RedirectResponse {
        $data = $request->validated();
        $accessoryOrder->transitionToStatus(
            $data['status'],
            $data['admin_notes'] ?? null,
            true,
            $request->user(),
        );

        return redirect()
            ->route('admin.accessory-orders.show', $accessoryOrder)
            ->with('success', 'Đơn phụ kiện đã được cập nhật.');
    }
}
