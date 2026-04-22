<?php

namespace App\Enums;

enum VehicleType: string
{
    case CAR = 'car';
    case MOTORBIKE = 'motorbike';

    public function label(): string
    {
        return match ($this) {
            self::CAR => 'Ô tô',
            self::MOTORBIKE => 'Xe máy',
        };
    }
}
