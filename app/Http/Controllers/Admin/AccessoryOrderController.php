<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccessoryOrderStatusRequest;
use App\Models\AccessoryOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccessoryOrderController extends Controller
{
    public function index(Request $request): View
    {
        $statusOptions = AccessoryOrder::statuses();

        $orders = AccessoryOrder::query()
            ->with(['product.category'])
            ->when(
                $request->filled('status') && in_array($request->string('status')->toString(), AccessoryOrder::statusValues(), true),
                fn ($query) => $query->where('status', $request->string('status')->toString())
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.accessory-orders.index', compact('orders', 'statusOptions'));
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
        $updates = [
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
        ];

        if (
            in_array($data['status'], [AccessoryOrder::STATUS_CONFIRMED, AccessoryOrder::STATUS_COMPLETED], true)
            && $accessoryOrder->confirmed_at === null
        ) {
            $updates['confirmed_at'] = now();
        }

        $accessoryOrder->update($updates);

        return redirect()
            ->route('admin.accessory-orders.show', $accessoryOrder)
            ->with('success', 'Đơn phụ kiện đã được cập nhật.');
    }
}
