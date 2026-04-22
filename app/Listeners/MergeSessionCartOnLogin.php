<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\CartService;
use Illuminate\Auth\Events\Login;

class MergeSessionCartOnLogin
{
    public function __construct(protected CartService $cartService) {}

    /**
     * Tự động merge giỏ hàng Guest (Session) vào Database khi User đăng nhập.
     */
    public function handle(Login $event): void
    {
        /** @var User $user */
        $user = $event->user;
        $this->cartService->mergeSessionCartToDatabase($user);
    }
}
