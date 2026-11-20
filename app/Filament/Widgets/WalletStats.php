<?php

namespace App\Filament\Widgets;

use App\Enums\WithdrawalStatus;
use App\Models\FreelancerWallet;
use App\Models\Withdrawal;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Ringkasan escrow dan penarikan di halaman Penarikan Saldo (tidak tampil di dashboard).
 */
class WalletStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $completed = Withdrawal::where('status', WithdrawalStatus::Completed);
        $open = Withdrawal::whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Processing]);

        return [
            Stat::make('Pendapatan komisi penarikan', Rupiah::format((int) (clone $completed)->sum('commission_amount')))
                ->description((clone $completed)->count().' penarikan berhasil ditransfer')
                ->color('success'),
            Stat::make('Penarikan menunggu', Rupiah::format((int) (clone $open)->sum('net_amount')))
                ->description((clone $open)->count().' pengajuan perlu diproses')
                ->color((clone $open)->exists() ? 'warning' : 'success'),
            Stat::make('Dana escrow (tertahan)', Rupiah::format((int) FreelancerWallet::sum('held_balance')))
                ->description('pesanan dibayar, belum disetujui customer'),
            Stat::make('Saldo tersedia freelancer', Rupiah::format((int) FreelancerWallet::sum('available_balance')))
                ->description('belum ditarik'),
        ];
    }
}
