<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessoryOrder extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'product_id',
        'customer_name',
        'customer_phone',
        'customer_address',
        'customer_email',
        'ai_visitor_id',
        'quantity',
        'notes',
        'status',
        'admin_notes',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_PENDING => 'Chờ xác nhận',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_CANCELLED => 'Đã hủy',
            self::STATUS_COMPLETED => 'Hoàn thành',
        ];
    }

    /**
     * @return list<string>
     */
    public static function statusValues(): array
    {
        return array_keys(self::statuses());
    }

    public function statusLabel(): string
    {
        return self::statuses()[$this->status] ?? $this->status;
    }

    public function statusColorClass(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => 'border-emerald-500/20 bg-emerald-500/5 text-emerald-500',
            self::STATUS_COMPLETED => 'border-[#1C69D4]/20 bg-[#1C69D4]/5 text-[#1C69D4]',
            self::STATUS_CANCELLED => 'border-rose-500/20 bg-rose-500/5 text-rose-500',
            default => 'border-yellow-500/20 bg-yellow-500/5 text-yellow-500',
        };
    }
}
