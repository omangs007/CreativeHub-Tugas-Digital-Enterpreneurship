<?php

namespace App\Policies;

use App\Models\User;

/**
 * Pengelolaan user di panel admin. Admin hanya dapat mengaktifkan/menonaktifkan akun
 * (tidak bisa menonaktifkan diri sendiri); data akun lain tidak diedit dari panel.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin() || $user->id === $model->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function toggleActive(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
