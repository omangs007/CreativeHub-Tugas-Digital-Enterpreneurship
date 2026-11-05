<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

/**
 * Percakapan hanya dapat dibaca dan dikirimi pesan oleh dua pesertanya.
 */
class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }

    public function send(User $user, Conversation $conversation): bool
    {
        return $conversation->hasParticipant($user);
    }
}
