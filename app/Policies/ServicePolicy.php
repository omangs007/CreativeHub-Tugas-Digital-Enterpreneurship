<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\User;

class ServicePolicy
{
    /**
     * Daftar seluruh layanan (panel admin).
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Service $service): bool
    {
        return $user->isAdmin() || $service->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Freelancer);
    }

    /**
     * Mengubah, mengajukan review, dan menarik layanan hanya boleh dilakukan pemiliknya.
     * Admin tidak mengedit konten freelancer; admin hanya memoderasi.
     */
    public function update(User $user, Service $service): bool
    {
        return $service->user_id === $user->id;
    }

    public function delete(User $user, Service $service): bool
    {
        return $service->user_id === $user->id;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function moderate(User $user, Service $service): bool
    {
        return $user->isAdmin() && $service->status === ServiceStatus::PendingReview;
    }
}
