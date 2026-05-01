<?php

namespace App\Enums;

enum VehicleType: string
{
    case CAR = 'car';
    case MOTORBIKE = 'motorbike';
    case ACCESSORY = 'accessory';

    public function label(): string
    {
        return match ($this) {
            self::CAR => 'Ô tô',
            self::MOTORBIKE => 'Xe máy',
            self::ACCESSORY => 'Phụ kiện',
        };
    }
}
