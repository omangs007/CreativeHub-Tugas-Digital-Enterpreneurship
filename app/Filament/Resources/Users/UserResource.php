<?php

namespace App\Filament\Resources\Users;

use App\Enums\RoleName;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Http\Controllers\AvatarController;
use App\Models\User;
use App\Support\AuditLogger;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Validation\Rules\Password;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $modelLabel = 'pengguna';

    protected static ?string $pluralModelLabel = 'pengguna';

    protected static ?string $navigationLabel = 'Pengguna';

    protected static string|UnitEnum|null $navigationGroup = 'Pengguna';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Akun')->columns(2)->schema([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn (?string $state) => mb_strtolower(trim((string) $state))),
                CheckboxList::make('role_names')
                    ->label('Peran')
                    ->options(collect(RoleName::cases())->mapWithKeys(fn (RoleName $r) => [$r->value => $r->label()])->all())
                    ->required()
                    ->columns(3)
                    ->helperText('Satu akun boleh Customer sekaligus Freelancer. Profil freelancer dibuat otomatis.')
                    // Admin tidak dapat mencabut peran Admin dari akunnya sendiri.
                    ->disableOptionWhen(fn (string $value, ?User $record) => $value === RoleName::Admin->value && $record?->is(auth()->user()))
                    ->columnSpanFull(),
            ]),
            Section::make('Foto profil')->schema([
                FileUpload::make('avatar_path')
                    ->hiddenLabel()
                    ->image()
                    ->avatar()
                    ->disk('public')
                    ->directory(fn (?User $record) => 'avatars/'.($record?->id ?? 'baru'))
                    ->visibility('public')
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->maxSize(AvatarController::MAX_KB)
                    ->helperText('JPG, PNG, atau WebP, maksimal 5 MB. Foto otomatis diperkecil. Kosongkan untuk memakai inisial nama.'),
            ]),
            Section::make('Password')->schema([
                TextInput::make('password')
                    ->label(fn (string $operation) => $operation === 'create' ? 'Password awal' : 'Reset password')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation) => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->helperText(fn (string $operation) => $operation === 'create'
                        ? 'Berikan password ini kepada pemilik akun dan minta ia menggantinya di halaman Profil.'
                        : 'Kosongkan jika tidak ingin mengganti password.'),
            ]),
        ]);
    }

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
                EditAction::make(),
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
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
