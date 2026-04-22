<?php

namespace App\Enums;

enum AppointmentType: string
{
    case TestDrive = 'test_drive';
    case Viewing = 'viewing';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::TestDrive => 'Lái thử xe',
            self::Viewing => 'Xem xe trực tiếp',
            self::Maintenance => 'Bảo dưỡng định kỳ',
        };
    }
}
