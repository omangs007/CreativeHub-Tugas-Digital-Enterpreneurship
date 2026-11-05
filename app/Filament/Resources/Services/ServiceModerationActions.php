<?php

namespace App\Filament\Resources\Services;

use App\Models\Service;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Aksi Setujui/Tolak yang dipakai di tabel dan halaman detail layanan.
 * Otorisasi lewat ServicePolicy::moderate (admin + status menunggu review).
 */
final class ServiceModerationActions
{
    public static function approve(): Action
    {
        return Action::make('approve')
            ->label('Setujui')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->authorize('moderate')
            ->requiresConfirmation()
            ->modalHeading('Setujui layanan?')
            ->modalDescription('Layanan akan langsung tampil di katalog publik.')
            ->action(function (Service $record) {
                $record->approve(auth()->user());

                Notification::make()->title('Layanan disetujui dan dipublikasikan.')->success()->send();
            });
    }

    public static function reject(): Action
    {
        return Action::make('reject')
            ->label('Tolak')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->authorize('moderate')
            ->modalHeading('Tolak layanan')
            ->schema([
                Textarea::make('reason')
                    ->label('Alasan penolakan')
                    ->helperText('Ditampilkan kepada freelancer agar bisa diperbaiki.')
                    ->required()
                    ->minLength(10)
                    ->maxLength(1000),
            ])
            ->action(function (Service $record, array $data) {
                $record->reject(auth()->user(), $data['reason']);

                Notification::make()->title('Layanan ditolak.')->warning()->send();
            });
    }
}
