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
        };
    }
}
