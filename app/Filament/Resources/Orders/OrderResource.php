<?php

namespace App\Filament\Resources\Orders;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Models\Order;
use App\Support\Rupiah;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pesanan hanya dapat dilihat admin; nilai transaksi tidak dapat diubah dari panel.
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $modelLabel = 'pesanan';

    protected static ?string $pluralModelLabel = 'pesanan';

    protected static ?string $navigationLabel = 'Pesanan';

    protected static string|UnitEnum|null $navigationGroup = 'Transaksi';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'freelancer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')->label('No. Pesanan')->searchable()->fontFamily('mono'),
                TextColumn::make('title')->label('Judul')->searchable()->limit(40),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('freelancer.name')->label('Freelancer')->searchable(),
                TextColumn::make('total_amount')->label('Total')->formatStateUsing(fn (int $state) => Rupiah::format($state))->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(OrderStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $money = fn (?int $state) => $state === null ? '-' : Rupiah::format($state);

        return $schema->components([
            Section::make('Pesanan')->columns(3)->schema([
                TextEntry::make('order_number')->label('No. Pesanan'),
                TextEntry::make('status')->badge(),
                TextEntry::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
                TextEntry::make('title')->label('Judul')->columnSpan(2),
                TextEntry::make('package_name')->label('Paket')->placeholder('Penawaran proyek'),
                TextEntry::make('customer.name')->label('Customer'),
                TextEntry::make('freelancer.name')->label('Freelancer'),
                TextEntry::make('delivery_days')->label('Pengerjaan')->suffix(' hari'),
                TextEntry::make('scope_description')->label('Ruang lingkup (snapshot)')->columnSpanFull(),
            ]),
            Section::make('Nominal (snapshot, tidak dapat diubah)')->columns(4)->schema([
                TextEntry::make('total_amount')->label('Total')->formatStateUsing($money),
                TextEntry::make('commission_rate_bps')->label('Komisi')->formatStateUsing(fn (int $state) => ($state / 100).'%'),
                TextEntry::make('commission_amount')->label('Komisi marketplace')->formatStateUsing($money),
                TextEntry::make('freelancer_amount')->label('Bagian freelancer')->formatStateUsing($money),
            ]),
            Section::make('Riwayat status')->schema([
                RepeatableEntry::make('statusHistories')->hiddenLabel()->columns(4)->schema([
                    TextEntry::make('to_status')->label('Status')->badge(),
                    TextEntry::make('changedBy.name')->label('Oleh')->placeholder('Sistem'),
                    TextEntry::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
                    TextEntry::make('note')->label('Catatan')->placeholder('-'),
                ]),
            ]),
            Section::make('Pembayaran')->schema([
                RepeatableEntry::make('payments')->hiddenLabel()->columns(4)->placeholder('Belum ada pembayaran.')->schema([
                    TextEntry::make('transaction_code')->label('Transaksi'),
                    TextEntry::make('method')->label('Metode'),
                    TextEntry::make('amount')->label('Nominal')->formatStateUsing($money),
                    TextEntry::make('status')->badge(),
                ]),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}
