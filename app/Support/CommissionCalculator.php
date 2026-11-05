<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * Pembagian total pembayaran menjadi komisi marketplace dan bagian freelancer.
 * Seluruhnya integer Rupiah; komisi dibulatkan ke bawah sehingga
 * komisi + bagian freelancer selalu sama dengan total.
 */
final class CommissionCalculator
{
    public static function currentRateBps(): int
    {
        return (int) config('creativehub.commission_rate_bps');
    }

    /**
     * @return array{rate_bps: int, commission: int, freelancer: int}
     */
    public static function split(int $total, ?int $rateBps = null): array
    {
        $rateBps ??= self::currentRateBps();

        if ($total <= 0) {
            throw new InvalidArgumentException('Total harus lebih dari 0.');
        }

        if ($rateBps < 0 || $rateBps > 10_000) {
            throw new InvalidArgumentException('Komisi harus di antara 0 dan 10000 basis poin.');
        }

        $commission = intdiv($total * $rateBps, 10_000);

        return [
            'rate_bps' => $rateBps,
            'commission' => $commission,
            'freelancer' => $total - $commission,
        ];
    }
}
