<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Events\OrderPaymentConfirmed;
use App\Models\Order;
use Exception;
use Illuminate\Support\Facades\DB;

class ConfirmOrderPaymentAction
{
    /**
     * Xác nhận thanh toán cho đơn hàng.
     * Chỉ cho phép nếu đơn hàng đang ở trạng thái pending_payment.
     *
     * @throws Exception
     */
    public function execute(Order $order): Order
    {
        // 1. STATE VALIDATION BẮT BUỘC
        if ($order->status !== OrderStatus::PendingPayment) {
            throw new Exception("Không thể xác nhận thanh toán. Đơn hàng đang ở trạng thái: {$order->status->value}");
        }

        return DB::transaction(function () use ($order) {
            // 2. Cập nhật trạng thái sang paid
            $order->update([
                'status' => OrderStatus::Paid,
            ]);

            // 3. Dispatch event (sau này Listener sẽ xử lý gửi Mail)
            event(new OrderPaymentConfirmed($order));

            return $order;
        });
    }
}
