<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Review $review): bool
    {
        return false;
    }

    /**
     * Admin hanya dapat menampilkan/menyembunyikan ulasan, tidak mengubah isi atau rating.
     */
    public function moderate(User $user, Review $review): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Review $review): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
