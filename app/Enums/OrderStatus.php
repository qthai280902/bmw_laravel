<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Chờ thanh toán',
            self::Paid => 'Đã thanh toán',
            self::Cancelled => 'Đã huỷ',
            self::Expired => 'Hết hạn',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PendingPayment => 'text-yellow-400',
            self::Paid => 'text-green-400',
            self::Cancelled => 'text-red-400',
            self::Expired => 'text-zinc-500',
        };
    }
}
