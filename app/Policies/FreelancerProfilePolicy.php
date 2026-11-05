<?php

namespace App\Policies;

use App\Models\FreelancerProfile;
use App\Models\User;

class FreelancerProfilePolicy
{
    /**
     * Setiap akun hanya boleh memiliki satu profil freelancer.
     */
    public function create(User $user): bool
    {
        return ! $user->freelancerProfile()->exists();
    }

    public function update(User $user, FreelancerProfile $profile): bool
    {
        return $profile->user_id === $user->id;
    }
}
