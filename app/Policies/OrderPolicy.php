<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Xác định xem người dùng có thể xem chi tiết đơn hàng hay không.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id;
    }
}
