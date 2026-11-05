<?php

namespace App\Support;

/**
 * Format nominal Rupiah. Nominal selalu integer (BIGINT), tidak pernah float.
 */
final class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
