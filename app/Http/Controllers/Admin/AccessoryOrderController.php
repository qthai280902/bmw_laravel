<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccessoryOrderStatusRequest;
use App\Mail\Customer\AccessoryOrderConfirmationMail;
use App\Mail\Customer\AccessoryOrderStatusMail;
use App\Models\AccessoryOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
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
            'statusOptions' => $accessoryOrder->allowedStatusOptions(),
        ]);
    }

    public function updateStatus(
        UpdateAccessoryOrderStatusRequest $request,
        AccessoryOrder $accessoryOrder
    ): RedirectResponse {
        $data = $request->validated();

        try {
            $accessoryOrder->transitionToStatus(
                $data['status'],
                $data['admin_notes'] ?? null,
                true,
                $request->user(),
            );
        } catch (InvalidStatusTransitionException $exception) {
            return back()
                ->withErrors(['status' => $exception->getMessage()])
                ->with('error', $exception->getMessage())
                ->withInput();
        }

        return redirect()
            ->route('admin.accessory-orders.show', $accessoryOrder)
            ->with('success', 'Đơn phụ kiện đã được cập nhật.');
    }

    /**
     * Xem trước trực tiếp 2 mẫu email giao dịch của Đơn phụ kiện (chỉ đọc).
     */
    public function previewMail(Request $request, AccessoryOrder $accessoryOrder): Mailable
    {
        $accessoryOrder->loadMissing(['product.category', 'product.primaryImage', 'product.images']);

        $mode = trim((string) $request->query('mode', 'confirmation'));

        return match ($mode) {
            'status' => new AccessoryOrderStatusMail($accessoryOrder),
            default => new AccessoryOrderConfirmationMail($accessoryOrder),
        };
    }
}
