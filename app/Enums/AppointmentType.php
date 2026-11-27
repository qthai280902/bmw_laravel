<?php

namespace App\Enums;

enum AppointmentType: string
{
    case TestDrive = 'test_drive';
    case Viewing = 'viewing';
    case Maintenance = 'maintenance';
    case Detailing = 'detailing';
    case CarWash = 'car_wash';
    case Quote = 'quote';
    case Consult = 'consult';
    case AdvisorMeeting = 'advisor_meeting';
    case TradeIn = 'trade_in';

    public function label(): string
    {
        return match ($this) {
            self::TestDrive => 'Lái thử xe',
            self::Viewing => 'Xem xe trực tiếp',
            self::Maintenance => 'Bảo dưỡng định kỳ',
            self::Detailing => 'Chăm sóc xe chuyên sâu',
            self::CarWash => 'Rửa xe Premium',
            self::Quote => 'Yêu cầu báo giá',
            self::Consult => 'Tư vấn trực tiếp',
            self::AdvisorMeeting => 'Gặp gỡ cố vấn',
            self::TradeIn => 'Đổi xe cũ',
        };
    }

    public function isServiceBooking(): bool
    {
        return in_array($this, [self::Maintenance, self::Detailing, self::CarWash], true);
    }

    public function isTradeIn(): bool
    {
        return $this === self::TradeIn;
    }

    public function isSalesLead(): bool
    {
        return ! $this->isServiceBooking() && ! $this->isTradeIn();
    }

    public function completionCategory(): string
    {
        if ($this->isServiceBooking()) {
            return 'service';
        }

        if ($this->isTradeIn()) {
            return 'trade_in';
        }

        return 'sales';
    }
}
