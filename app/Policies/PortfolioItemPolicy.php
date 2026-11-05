<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, PortfolioItem $item): bool
    {
        return $user->isAdmin() || $item->user_id === $user->id;
    }

    /**
     * Admin menampilkan/menyembunyikan portofolio (moderasi).
     */
    public function moderate(User $user, PortfolioItem $item): bool
    {
        return $user->isAdmin();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Freelancer);
    }

    public function update(User $user, PortfolioItem $item): bool
    {
        return $item->user_id === $user->id;
    }

    public function delete(User $user, PortfolioItem $item): bool
    {
        return $item->user_id === $user->id;
    }
}
