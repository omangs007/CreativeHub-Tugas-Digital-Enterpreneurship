<?php

namespace App\Filament\Resources\Withdrawals;

use App\Enums\WithdrawalStatus;
use App\Filament\Resources\Withdrawals\Pages\ListWithdrawals;
use App\Models\Withdrawal;
use App\Services\Wallet;
use App\Support\Rupiah;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;
use UnitEnum;

/**
 * Antrean penarikan saldo freelancer. Admin memproses transfer lalu mengonfirmasi
 * keberhasilannya (dengan nomor referensi transfer) atau menolaknya (saldo dikembalikan).
 */
class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowUpTray;

    protected static ?string $modelLabel = 'penarikan';

    protected static ?string $pluralModelLabel = 'penarikan saldo';

    protected static ?string $navigationLabel = 'Penarikan Saldo';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $open = Withdrawal::whereIn('status', [WithdrawalStatus::Pending, WithdrawalStatus::Processing])->count();

        return $open > 0 ? (string) $open : null;
    }

    public static function table(Table $table): Table
    {
        $money = fn (int $state) => Rupiah::format($state);

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['user', 'processor']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('reference')->label('Referensi')->searchable()->fontFamily('mono'),
                TextColumn::make('user.name')->label('Freelancer')->searchable(),
                TextColumn::make('bank_name')->label('Tujuan')
                    ->description(fn (Withdrawal $record) => $record->account_number.' · a.n. '.$record->account_holder),
                TextColumn::make('amount')->label('Nominal')->formatStateUsing($money)->sortable(),
                TextColumn::make('commission_amount')->label('Komisi')->formatStateUsing($money)
                    ->description(fn (Withdrawal $record) => ($record->commission_rate_bps / 100).'%'),
                TextColumn::make('net_amount')->label('Ditransfer')->formatStateUsing($money)->weight('bold'),
                TextColumn::make('status')->badge(),
                TextColumn::make('transfer_reference')->label('Ref. transfer')->placeholder('-')->toggleable(),
                TextColumn::make('admin_note')->label('Catatan')->placeholder('-')->limit(40)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Diajukan')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('processor.name')->label('Diproses oleh')->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(WithdrawalStatus::class),
            ])
            ->recordActions([
                Action::make('process')
                    ->label('Proses Transfer')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('info')
                    ->authorize('process')
                    ->visible(fn (Withdrawal $record) => $record->status === WithdrawalStatus::Pending)
                    ->requiresConfirmation()
                    ->modalHeading('Proses penarikan?')
                    ->modalDescription(fn (Withdrawal $record) => 'Transfer '.Rupiah::format($record->net_amount).' ke '.$record->bank_name.' '.$record->account_number.' a.n. '.$record->account_holder.'.')
                    ->action(fn (Withdrawal $record) => self::run(fn () => app(Wallet::class)->markProcessing($record, auth()->user()), 'Penarikan ditandai sedang ditransfer.')),
                Action::make('complete')
                    ->label('Konfirmasi Berhasil')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->authorize('process')
                    ->visible(fn (Withdrawal $record) => $record->status === WithdrawalStatus::Processing)
                    ->modalHeading('Konfirmasi transfer berhasil')
                    ->modalDescription(fn (Withdrawal $record) => 'Pastikan '.Rupiah::format($record->net_amount).' sudah diterima rekening tujuan. Komisi '.Rupiah::format($record->commission_amount).' dicatat sebagai pendapatan platform.')
                    ->schema([
                        TextInput::make('transfer_reference')->label('Nomor referensi transfer')->required()->maxLength(100),
                    ])
                    ->action(fn (Withdrawal $record, array $data) => self::run(fn () => app(Wallet::class)->markCompleted($record, auth()->user(), trim($data['transfer_reference'])), 'Penarikan berhasil dikonfirmasi.')),
                Action::make('reject')
                    ->label('Tolak / Gagal')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->authorize('process')
                    ->visible(fn (Withdrawal $record) => $record->status->isOpen())
                    ->modalHeading('Tolak atau gagalkan penarikan')
                    ->modalDescription(fn (Withdrawal $record) => 'Saldo '.Rupiah::format($record->amount).' akan dikembalikan utuh ke saldo tersedia freelancer.')
                    ->schema([
                        Textarea::make('reason')->label('Alasan')->helperText('Ditampilkan kepada freelancer.')->required()->minLength(5)->maxLength(500),
                    ])
                    ->action(fn (Withdrawal $record, array $data) => self::run(fn () => app(Wallet::class)->reject($record, auth()->user(), trim($data['reason'])), 'Penarikan ditolak, saldo dikembalikan.', 'warning')),
            ]);
    }

    private static function run(callable $callback, string $message, string $type = 'success'): void
    {
        try {
            $callback();
            Notification::make()->title($message)->{$type}()->send();
        } catch (ValidationException $e) {
            Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();
        }
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWithdrawals::route('/'),
        ];
    }
}
