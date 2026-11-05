<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Role;
use App\Models\User;
use App\Support\AuditLogger;
use App\Support\AvatarImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Pengelolaan akun oleh admin (panel Filament). Setiap perubahan dicatat di audit log;
 * password tidak pernah ditulis ke audit log.
 */
class UserAdministration
{
    private const AVATAR_DISK = 'public';

    /**
     * @param  array{name: string, email: string, password: string, role_names: list<string>, avatar_path?: ?string}  $data
     */
    public function create(array $data, User $admin): User
    {
        $roles = $this->parseRoles($data['role_names'] ?? []);

        return DB::transaction(function () use ($data, $roles, $admin) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);
            $avatar = $data['avatar_path'] ?? null;

            $user->forceFill([
                'email_verified_at' => now(),
                'avatar_path' => $avatar ? AvatarImage::normalize($avatar, $user->id) : null,
            ])->save();

            $this->syncRoles($user, $roles);

            AuditLogger::record('user.created_by_admin', $user, null, [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => array_map(fn (RoleName $r) => $r->value, $roles),
            ], $admin);

            return $user;
        });
    }

    /**
     * @param  array{name: string, email: string, password?: ?string, role_names: list<string>, avatar_path?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function update(User $user, array $data, User $admin): User
    {
        $roles = $this->parseRoles($data['role_names'] ?? []);

        if ($user->is($admin) && ! in_array(RoleName::Admin, $roles, true)) {
            throw ValidationException::withMessages([
                'data.role_names' => __('Anda tidak dapat mencabut peran Admin dari akun Anda sendiri.'),
            ]);
        }

        $oldAvatar = $user->avatar_path;
        $newAvatar = $data['avatar_path'] ?? null;

        // Foto baru (berbeda dari yang tersimpan) diperkecil menjadi 512×512 WebP.
        if ($newAvatar && $newAvatar !== $oldAvatar) {
            $newAvatar = AvatarImage::normalize($newAvatar, $user->id);
        }

        DB::transaction(function () use ($user, $data, $roles, $admin, $oldAvatar, $newAvatar) {
            $old = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->sort()->values()->all(),
            ];

            $user->name = $data['name'];
            $user->email = $data['email'];
            if (filled($data['password'] ?? null)) {
                $user->password = $data['password'];
            }
            $user->avatar_path = $newAvatar;
            $passwordChanged = $user->isDirty('password');
            $user->save();

            $this->syncRoles($user, $roles);

            $new = [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => collect($roles)->map(fn (RoleName $r) => $r->value)->sort()->values()->all(),
            ];

            $changedOld = array_filter($old, fn ($value, $key) => $value !== $new[$key], ARRAY_FILTER_USE_BOTH);
            $changedNew = array_intersect_key($new, $changedOld);

            if ($passwordChanged) {
                $changedNew['password'] = 'direset admin';
            }
            if ($oldAvatar !== $newAvatar) {
                $changedNew['avatar'] = $newAvatar ? 'diganti' : 'dihapus';
            }

            if ($changedNew !== []) {
                AuditLogger::record('user.updated_by_admin', $user, $changedOld ?: null, $changedNew, $admin);
            }
        });

        if ($oldAvatar && $oldAvatar !== $newAvatar) {
            Storage::disk(self::AVATAR_DISK)->delete($oldAvatar);
        }

        return $user->refresh();
    }

    /**
     * @param  list<RoleName>  $roles
     */
    private function syncRoles(User $user, array $roles): void
    {
        $ids = Role::whereIn('name', array_map(fn (RoleName $r) => $r->value, $roles))->pluck('id')->all();
        $user->roles()->sync($ids);
        $user->unsetRelation('roles');

        // Freelancer selalu memiliki profil agar tampil konsisten di katalog dan profil publik.
        if (in_array(RoleName::Freelancer, $roles, true) && ! $user->freelancerProfile()->exists()) {
            $user->freelancerProfile()->create();
        }
    }

    /**
     * @param  list<string>  $names
     * @return list<RoleName>
     *
     * @throws ValidationException
     */
    private function parseRoles(array $names): array
    {
        $roles = array_values(array_unique(array_map(fn (string $n) => RoleName::from($n), $names), SORT_REGULAR));

        if ($roles === []) {
            throw ValidationException::withMessages(['data.role_names' => __('Pilih minimal satu peran.')]);
        }

        return $roles;
    }
}
