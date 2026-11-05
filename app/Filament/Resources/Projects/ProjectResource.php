<?php

namespace App\Filament\Resources\Projects;

use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Models\Project;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $modelLabel = 'proyek';

    protected static ?string $pluralModelLabel = 'proyek';

    protected static ?string $navigationLabel = 'Proyek';

    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['customer', 'category'])->withCount('proposals'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')->label('Judul')->searchable()->limit(50)->wrap()
                    ->url(fn (Project $record) => $record->isOpen() ? route('projects.show', $record) : null, shouldOpenInNewTab: true),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('category.name')->label('Kategori'),
                TextColumn::make('proposals_count')->label('Penawaran')->numeric(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->label('Diposting')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options(ProjectStatus::class),
            ])
            ->recordActions([
                Action::make('close')
                    ->label('Tutup')
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->authorize('moderate')
                    ->requiresConfirmation()
                    ->modalDescription('Proyek tidak lagi tampil publik dan tidak menerima penawaran.')
                    ->action(function (Project $record) {
                        $record->close();

                        AuditLogger::record('project.closed_by_admin', $record, ['status' => ProjectStatus::Open->value], ['status' => $record->status->value]);

                        Notification::make()->title('Proyek ditutup.')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
        ];
    }
}
