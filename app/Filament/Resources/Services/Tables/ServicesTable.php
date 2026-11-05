<?php

namespace App\Filament\Resources\Services\Tables;

use App\Enums\ServiceStatus;
use App\Filament\Resources\Services\ServiceModerationActions;
use App\Support\Rupiah;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query
                ->with(['freelancer', 'category'])
                ->withMin('activePackages as starting_price', 'price'))
            ->defaultSort('updated_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50)
                    ->wrap(),
                TextColumn::make('freelancer.name')
                    ->label('Freelancer')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Kategori'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('starting_price')
                    ->label('Harga mulai')
                    ->formatStateUsing(fn (?int $state) => $state === null ? '-' : Rupiah::format($state)),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(ServiceStatus::class),
                SelectFilter::make('category')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                ServiceModerationActions::approve(),
                ServiceModerationActions::reject(),
            ]);
    }
}
