<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    /** Ikon yang tersedia di komponen <x-lucide>. */
    public const ICONS = [
        'gamepad' => 'Game (Roblox)',
        'code' => 'Kode (Website)',
        'palette' => 'Palet (Desain)',
        'sparkles' => 'Kilau (Lainnya)',
    ];

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(100)
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (Set $set, ?string $state, string $operation) {
                        if ($operation === 'create') {
                            $set('slug', Str::slug((string) $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label('Slug URL')
                    ->required()
                    ->maxLength(120)
                    ->alphaDash()
                    ->unique(ignoreRecord: true)
                    ->helperText('Dipakai di URL /categories/{slug}. Mengubahnya akan mengubah tautan kategori.'),
                Textarea::make('description')
                    ->label('Deskripsi')
                    ->maxLength(500)
                    ->columnSpanFull(),
                Select::make('icon')
                    ->label('Ikon')
                    ->options(self::ICONS)
                    ->default('sparkles')
                    ->required(),
                TextInput::make('sort_order')
                    ->label('Urutan')
                    ->required()
                    ->integer()
                    ->minValue(0)
                    ->maxValue(1000)
                    ->default(0),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->helperText('Kategori nonaktif beserta layanannya disembunyikan dari katalog publik.')
                    ->default(true),
            ]);
    }
}
