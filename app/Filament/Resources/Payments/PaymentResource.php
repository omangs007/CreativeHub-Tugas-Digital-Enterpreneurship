<?php

namespace App\Filament\Resources\Payments;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use App\Support\Rupiah;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pembayaran simulasi, hanya dapat dilihat admin.
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $modelLabel = 'pembayaran';

    protected static ?string $pluralModelLabel = 'pembayaran';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 2;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'customer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('transaction_code')->label('Transaksi')->searchable()->fontFamily('mono'),
                TextColumn::make('order.order_number')->label('Pesanan')->searchable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('method')->label('Metode')->badge(),
                TextColumn::make('amount')->label('Nominal')->formatStateUsing(fn (int $state) => Rupiah::format($state))->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('paid_at')->label('Dibayar')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')->options(PaymentStatus::class),
                SelectFilter::make('method')->label('Metode')->options(PaymentMethod::class),
            ])
            ->recordActions([
                Action::make('receipt')
                    ->label('Struk')
                    ->icon(Heroicon::OutlinedPrinter)
                    ->url(fn (Payment $record) => route('receipts.show', $record), shouldOpenInNewTab: true)
                    ->visible(fn (Payment $record) => $record->status === PaymentStatus::Success),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
        ];
    }
}
