<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Freelancer);
    }

    /**
     * Mengubah, mengajukan review, dan menarik layanan hanya boleh dilakukan pemiliknya.
     */
    public function update(User $user, Service $service): bool
    {
        return $service->user_id === $user->id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $service->user_id === $user->id;
    }
}
