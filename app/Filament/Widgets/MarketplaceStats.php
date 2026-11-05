<?php

namespace App\Filament\Widgets;

use App\Enums\RoleName;
use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\ServiceResource;
use App\Models\Service;
use App\Models\User;
use App\Services\FinancialReport;
use App\Support\Rupiah;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MarketplaceStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        $byRole = fn (RoleName $role) => User::whereHas('roles', fn ($q) => $q->where('name', $role->value))->count();

        $pending = Service::where('status', ServiceStatus::PendingReview)->count();

        $month = FinancialReport::fromInput(['preset' => 'this_month'])->summary();

        return [
            Stat::make('Omzet bulan ini', Rupiah::format($month['revenue']))
                ->description($month['success_count'].' transaksi berhasil · komisi '.Rupiah::format($month['commission']))
                ->color('success')
                ->url(route('admin.financial-reports')),
            Stat::make('Layanan menunggu review', $pending)
                ->description($pending > 0 ? 'Perlu dimoderasi' : 'Tidak ada antrean')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url(ServiceResource::getUrl('index', ['filters' => ['status' => ['value' => ServiceStatus::PendingReview->value]]])),
            Stat::make('Layanan dipublikasikan', Service::where('status', ServiceStatus::Published)->count()),
            Stat::make('Freelancer', $byRole(RoleName::Freelancer)),
            Stat::make('Customer', $byRole(RoleName::Customer)),
        ];
    }
}
