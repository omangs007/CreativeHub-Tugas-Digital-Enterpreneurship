<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Audit log hanya bisa dibaca (lihat AuditLogPolicy).
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $modelLabel = 'audit log';

    protected static ?string $pluralModelLabel = 'audit log';

    protected static ?string $navigationLabel = 'Audit Log';

    protected static string|UnitEnum|null $navigationGroup = 'Sistem';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Pelaku')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Objek')
                    ->formatStateUsing(fn (?string $state, AuditLog $record) => $state ? class_basename($state).' #'.$record->auditable_id : '-'),
                TextColumn::make('old_values')
                    ->label('Sebelum')
                    ->formatStateUsing(fn ($state) => self::summarize($state))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('new_values')
                    ->label('Sesudah')
                    ->formatStateUsing(fn ($state) => self::summarize($state))
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options(fn () => AuditLog::query()->distinct()->orderBy('action')->pluck('action', 'action')->all()),
            ]);
    }

    /**
     * @param  array<string, mixed>|string|null  $values
     */
    private static function summarize(array|string|null $values): string
    {
        if (! is_array($values) || $values === []) {
            return '-';
        }

        return collect($values)
            ->map(fn ($value, $key) => $key.': '.(is_bool($value) ? ($value ? 'ya' : 'tidak') : (is_scalar($value) || $value === null ? (string) $value : json_encode($value))))
            ->implode(' · ');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
        ];
    }
}
