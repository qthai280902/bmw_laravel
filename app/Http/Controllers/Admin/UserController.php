<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\User;

class UserController extends Controller
{
    /**
     * Danh sách khách hàng với phân hạng VIP (N+1 Optimized).
     */
    public function index()
    {
        // CHÚ Ý: Load withCount để accessor vip_tier chạy hiệu năng cao
        $users = User::withCount(['orders' => function ($query) {
            $query->where('status', OrderStatus::Paid);
        }])
            ->latest()
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }
}
