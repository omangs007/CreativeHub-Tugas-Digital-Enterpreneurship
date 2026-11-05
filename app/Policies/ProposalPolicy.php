<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function view(User $user, Proposal $proposal): bool
    {
        return $user->isAdmin()
            || $proposal->freelancer_id === $user->id
            || $proposal->project->customer_id === $user->id;
    }

    /**
     * Freelancer pemilik dapat mengubah atau menarik penawaran selama masih menunggu
     * dan proyek masih terbuka.
     */
    public function update(User $user, Proposal $proposal): bool
    {
        return $proposal->freelancer_id === $user->id
            && $proposal->isPending()
            && $proposal->project->isOpen();
    }

    /**
     * Customer pemilik proyek dapat menerima atau menolak penawaran yang menunggu.
     */
    public function decide(User $user, Proposal $proposal): bool
    {
        return $proposal->project->customer_id === $user->id
            && $proposal->isPending()
            && $proposal->project->isOpen();
    }
}
