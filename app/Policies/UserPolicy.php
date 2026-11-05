<?php

namespace App\Policies;

use App\Models\User;

/**
 * Pengelolaan user di panel admin: tambah, edit (nama, email, peran, foto, reset password),
 * dan aktif/nonaktif. Admin tidak bisa menonaktifkan dirinya sendiri; akun tidak dihapus
 * agar riwayat transaksi tetap utuh.
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
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
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
