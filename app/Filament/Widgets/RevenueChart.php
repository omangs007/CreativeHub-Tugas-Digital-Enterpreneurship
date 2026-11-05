<?php

namespace App\Filament\Widgets;

use App\Services\FinancialReport;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

/**
 * Omzet terealisasi 30 hari terakhir (pembayaran simulasi berhasil), dari sumber angka
 * yang sama dengan laporan keuangan.
 */
class RevenueChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Omzet 30 hari terakhir (transaksi simulasi berhasil)';

    protected ?string $maxHeight = '260px';

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $today = CarbonImmutable::now();
        $daily = FinancialReport::fromInput([
            'preset' => 'custom',
            'start' => $today->subDays(29)->toDateString(),
            'end' => $today->toDateString(),
        ])->dailyRevenue();

        return [
            'datasets' => [
                [
                    'label' => 'Omzet (Rp)',
                    'data' => array_values($daily),
                    'borderColor' => '#7c3aed',
                    'backgroundColor' => 'rgba(124, 58, 237, 0.15)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => array_map(fn (string $d) => CarbonImmutable::parse($d)->translatedFormat('d M'), array_keys($daily)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
