<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('roles'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('roles.label')->label('Peran')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Terdaftar')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('roles')->label('Peran')->relationship('roles', 'label'),
                TernaryFilter::make('is_active')->label('Status akun')->trueLabel('Aktif')->falseLabel('Nonaktif'),
            ])
            ->recordActions([
                Action::make('toggleActive')
                    ->label(fn (User $record) => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (User $record) => $record->is_active ? Heroicon::OutlinedNoSymbol : Heroicon::OutlinedCheckCircle)
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->authorize('toggleActive')
                    ->requiresConfirmation()
                    ->modalDescription(fn (User $record) => $record->is_active
                        ? 'Akun tidak dapat login, dan layanan/proyeknya disembunyikan dari publik.'
                        : 'Akun dapat login kembali.')
                    ->action(function (User $record) {
                        $old = $record->is_active;
                        $record->is_active = ! $old;
                        $record->save();

                        AuditLogger::record($record->is_active ? 'user.activated' : 'user.deactivated', $record, ['is_active' => $old], ['is_active' => $record->is_active]);

                        Notification::make()->title($record->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
        ];
    }
}
