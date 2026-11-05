<?php

namespace App\Enums;

enum FreelancerAvailability: string
{
    case Available = 'available';
    case Busy = 'busy';
    case Unavailable = 'unavailable';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'Tersedia',
            self::Busy => 'Sibuk',
            self::Unavailable => 'Tidak tersedia',
        };
    }
}
