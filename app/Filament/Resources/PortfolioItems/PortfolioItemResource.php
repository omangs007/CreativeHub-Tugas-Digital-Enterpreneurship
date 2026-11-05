<?php

namespace App\Filament\Resources\PortfolioItems;

use App\Filament\Resources\PortfolioItems\Pages\ListPortfolioItems;
use App\Models\PortfolioItem;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class PortfolioItemResource extends Resource
{
    protected static ?string $model = PortfolioItem::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static ?string $modelLabel = 'portofolio';

    protected static ?string $pluralModelLabel = 'portofolio';

    protected static ?string $navigationLabel = 'Portofolio';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 5;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['freelancer', 'category']))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')->label('Gambar')->disk(PortfolioItem::DISK)->square(),
                TextColumn::make('title')->label('Judul')->searchable(),
                TextColumn::make('freelancer.name')->label('Freelancer')->searchable(),
                TextColumn::make('category.name')->label('Kategori')->placeholder('-'),
                IconColumn::make('is_visible')->label('Tampil')->boolean(),
                TextColumn::make('created_at')->label('Diunggah')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')->label('Tampil publik'),
            ])
            ->recordActions([
                Action::make('toggleVisibility')
                    ->label(fn (PortfolioItem $record) => $record->is_visible ? 'Sembunyikan' : 'Tampilkan')
                    ->icon(fn (PortfolioItem $record) => $record->is_visible ? Heroicon::OutlinedEyeSlash : Heroicon::OutlinedEye)
                    ->color(fn (PortfolioItem $record) => $record->is_visible ? 'danger' : 'success')
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->action(function (PortfolioItem $record) {
                        $old = $record->is_visible;
                        $record->is_visible = ! $old;
                        $record->save();

                        AuditLogger::record($record->is_visible ? 'portfolio.shown' : 'portfolio.hidden', $record, ['is_visible' => $old], ['is_visible' => $record->is_visible]);

                        Notification::make()->title($record->is_visible ? 'Portofolio ditampilkan.' : 'Portofolio disembunyikan.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortfolioItems::route('/'),
        ];
    }
}
