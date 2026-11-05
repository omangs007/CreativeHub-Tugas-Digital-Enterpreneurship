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

    /**
     * Normalisasi input form: "1.250.000" menjadi "1250000". Nilai lain dikembalikan apa adanya
     * agar tetap ditolak oleh aturan validasi integer (misalnya "150000.50").
     */
    public static function normalizeInput(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^\d{1,3}(\.\d{3})+$/', trim($value))) {
            return str_replace('.', '', trim($value));
        }

        return $value;
    }
}
