<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Enums\RoleName;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Pemilik dan admin boleh melihat proyek apa pun statusnya. Publik hanya
     * melihat proyek terbuka (dicek di controller publik).
     */
    public function view(User $user, Project $project): bool
    {
        return $user->isAdmin() || $project->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Customer);
    }

    /**
     * Proyek hanya bisa diubah pemiliknya selama masih terbuka.
     */
    public function update(User $user, Project $project): bool
    {
        return $project->customer_id === $user->id && $project->isOpen();
    }

    /**
     * Freelancer boleh menawar proyek terbuka milik orang lain, satu penawaran aktif per proyek.
     */
    public function propose(User $user, Project $project): bool
    {
        return $user->hasRole(RoleName::Freelancer)
            && $project->customer_id !== $user->id
            && $project->isOpen()
            && ! $project->proposals()
                ->where('freelancer_id', $user->id)
                ->whereIn('status', [ProposalStatus::Pending, ProposalStatus::Accepted])
                ->exists();
    }

    public function close(User $user, Project $project): bool
    {
        return $project->customer_id === $user->id;
    }

    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
