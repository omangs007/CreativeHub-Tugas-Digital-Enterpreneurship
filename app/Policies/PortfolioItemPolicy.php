<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\PortfolioItem;
use App\Models\User;

class PortfolioItemPolicy
{
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
