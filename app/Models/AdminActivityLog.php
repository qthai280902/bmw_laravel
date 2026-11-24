<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'user_email',
        'user_role',
        'action',
        'description',
        'ip_address',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function roleLabel(): string
    {
        return User::roles()[$this->user_role] ?? strtoupper($this->user_role);
    }

    public function actionLabel(): string
    {
        return match ($this->action) {
            'auth.login' => 'Đăng nhập',
            'auth.logout' => 'Đăng xuất',
            'promotion.dispatched' => 'Gửi ưu đãi',
            'appointment.status_changed' => 'Cập nhật lịch hẹn',
            'staff.created' => 'Tạo nhân sự',
            'staff.updated' => 'Cập nhật nhân sự',
            'staff.role_updated' => 'Đổi vai trò',
            'staff.locked' => 'Khóa tài khoản',
            'staff.unlocked' => 'Mở khóa tài khoản',
            'product.updated' => 'Cập nhật sản phẩm',
            default => $this->action,
        };
    }
}
