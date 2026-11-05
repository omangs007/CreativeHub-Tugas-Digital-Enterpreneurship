<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

    protected static ?string $modelLabel = 'ulasan';

    protected static ?string $pluralModelLabel = 'ulasan';

    protected static ?string $navigationLabel = 'Ulasan';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 4;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['order', 'customer', 'freelancer']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order.order_number')->label('Pesanan')->searchable(),
                TextColumn::make('customer.name')->label('Customer'),
                TextColumn::make('freelancer.name')->label('Freelancer'),
                TextColumn::make('rating')->label('Rating')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state)),
                TextColumn::make('comment')->label('Komentar')->limit(60)->wrap()->placeholder('-'),
                IconColumn::make('is_visible')->label('Tampil')->boolean(),
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')->label('Tampil publik'),
            ])
            ->recordActions([
                Action::make('toggleVisibility')
                    ->label(fn (Review $record) => $record->is_visible ? 'Sembunyikan' : 'Tampilkan')
                    ->icon(fn (Review $record) => $record->is_visible ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
                    ->color(fn (Review $record) => $record->is_visible ? 'danger' : 'success')
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->action(function (Review $record) {
                        $old = $record->is_visible;
                        $record->is_visible = ! $old;
                        $record->save();

                        AuditLogger::record($record->is_visible ? 'review.shown' : 'review.hidden', $record, ['is_visible' => $old], ['is_visible' => $record->is_visible]);

                        Notification::make()->title($record->is_visible ? 'Ulasan ditampilkan.' : 'Ulasan disembunyikan.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
        ];
    }
}
