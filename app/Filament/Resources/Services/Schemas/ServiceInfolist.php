<?php

namespace App\Filament\Resources\Services\Schemas;

use App\Support\Rupiah;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ServiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Layanan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')->label('Judul')->columnSpanFull(),
                        TextEntry::make('freelancer.name')->label('Freelancer'),
                        TextEntry::make('category.name')->label('Kategori'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('description')->label('Deskripsi')->columnSpanFull()->prose(),
                        TextEntry::make('rejection_reason')->label('Alasan penolakan')->columnSpanFull()->placeholder('-'),
                        TextEntry::make('moderator.name')->label('Dimoderasi oleh')->placeholder('-'),
                        TextEntry::make('moderated_at')->label('Waktu moderasi')->dateTime('d M Y H:i')->placeholder('-'),
                    ]),
                Section::make('Paket')
                    ->schema([
                        RepeatableEntry::make('packages')
                            ->hiddenLabel()
                            ->columns(5)
                            ->schema([
                                TextEntry::make('tier')->label('Tier')->formatStateUsing(fn ($state) => $state->label()),
                                TextEntry::make('name')->label('Nama'),
                                TextEntry::make('price')->label('Harga')->formatStateUsing(fn (int $state) => Rupiah::format($state)),
                                TextEntry::make('delivery_days')->label('Pengerjaan')->suffix(' hari'),
                                IconEntry::make('is_active')->label('Aktif')->boolean(),
                                TextEntry::make('description')->label('Isi paket')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }
}
