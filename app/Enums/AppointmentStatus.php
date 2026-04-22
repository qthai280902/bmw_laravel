<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Chờ xác nhận',
            self::Confirmed => 'Đã xác nhận',
            self::Completed => 'Đã hoàn thành',
            self::Cancelled => 'Đã hủy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'text-yellow-500 border-yellow-500/20 bg-yellow-500/5',
            self::Confirmed => 'text-emerald-500 border-emerald-500/20 bg-emerald-500/5',
            self::Completed => 'text-[#1C69D4] border-[#1C69D4]/20 bg-[#1C69D4]/5',
            self::Cancelled => 'text-rose-500 border-rose-500/20 bg-rose-500/5',
        };
    }
}
