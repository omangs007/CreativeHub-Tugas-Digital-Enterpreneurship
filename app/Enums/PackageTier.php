<?php

namespace App\Enums;

enum PackageTier: string
{
    case Basic = 'basic';
    case Standard = 'standard';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Basic => 'Basic',
            self::Standard => 'Standard',
            self::Premium => 'Premium',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Basic => 1,
            self::Standard => 2,
            self::Premium => 3,
        };
    }
}
