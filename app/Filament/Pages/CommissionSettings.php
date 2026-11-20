<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\AuditLogger;
use App\Support\CommissionCalculator;
use App\Support\Rupiah;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Admin mengatur persentase komisi marketplace. Nilai di-snapshot ke setiap order saat
 * dibuat, sehingga perubahan hanya berlaku untuk order baru. Setiap perubahan diaudit.
 */
class CommissionSettings extends Page
{
    public const MAX_PERCENT = 50;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?string $navigationLabel = 'Pengaturan Komisi';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?string $title = 'Pengaturan Komisi';

    protected static ?string $slug = 'commission-settings';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isAdmin();
    }

    public static function formatPercent(int $bps): string
    {
        return rtrim(rtrim(number_format($bps / 100, 2, ',', '.'), '0'), ',').'%';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Komisi marketplace')
                ->description('Komisi platform dipotong saat freelancer menarik saldo (bukan saat pembayaran), dan hanya sekali per penarikan. Pencatatan simulasi, bukan payout uang sungguhan.')
                ->schema([
                    TextEntry::make('current')
                        ->label('Komisi berlaku untuk penarikan baru')
                        ->state(fn () => self::formatPercent(CommissionCalculator::currentRateBps()))
                        ->size('lg')
                        ->weight('bold'),
                    TextEntry::make('example')
                        ->label('Contoh penarikan Rp500.000')
                        ->state(function () {
                            $split = CommissionCalculator::split(500_000);

                            return 'Komisi '.Rupiah::format($split['commission']).' · Diterima freelancer '.Rupiah::format($split['freelancer']);
                        }),
                    TextEntry::make('note')
                        ->hiddenLabel()
                        ->state('Penarikan yang sudah diajukan tetap memakai komisi yang tersimpan saat pengajuan. Dana pesanan masuk saldo freelancer secara penuh.'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('updateCommission')
                ->label('Ubah Komisi')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->authorize(fn () => self::canAccess())
                ->fillForm(fn () => ['percent' => CommissionCalculator::currentRateBps() / 100])
                ->schema([
                    TextInput::make('percent')
                        ->label('Persentase komisi')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(self::MAX_PERCENT)
                        ->step(0.01)
                        ->suffix('%')
                        ->required(),
                ])
                ->action(function (array $data) {
                    $old = CommissionCalculator::currentRateBps();
                    $new = (int) round(((float) $data['percent']) * 100);

                    Setting::put(Setting::COMMISSION_RATE_BPS, (string) $new);
                    AuditLogger::record('setting.commission_updated', null, ['commission_rate_bps' => $old], ['commission_rate_bps' => $new]);

                    Notification::make()->title('Komisi diperbarui menjadi '.self::formatPercent($new).'.')->success()->send();
                }),
        ];
    }
}
