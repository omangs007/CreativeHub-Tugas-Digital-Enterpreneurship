<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Withdrawal;

/**
 * Penarikan diajukan freelancer lewat halaman Saldo (bukan lewat panel) dan hanya
 * diproses admin. Data penarikan tidak pernah diubah atau dihapus manual.
 */
class WithdrawalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Withdrawal $withdrawal): bool
    {
        return $user->isAdmin() || $withdrawal->user_id === $user->id;
    }

    public function process(User $user, Withdrawal $withdrawal): bool
    {
        return $user->isAdmin() && $withdrawal->status->isOpen();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Withdrawal $withdrawal): bool
    {
        return false;
    }

    public function delete(User $user, Withdrawal $withdrawal): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
