<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Services\UserAdministration;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['role_names'] = $this->getRecord()->roles()->pluck('name')->all();
        unset($data['password']);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UserAdministration::class)->update($record, $data, auth()->user());
    }

    /**
     * Isi ulang form dari database: foto yang diunggah sudah diganti versi WebP, sehingga
     * path upload asli di form tidak boleh dipakai lagi pada simpan berikutnya.
     */
    protected function afterSave(): void
    {
        $this->fillForm();
    }
}
