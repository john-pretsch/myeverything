<?php

namespace App\Policies;

use App\Models\BrainiacAttempt;
use App\Models\User;

class BrainiacAttemptPolicy
{
    public function view(User $user, BrainiacAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }

    public function update(User $user, BrainiacAttempt $attempt): bool
    {
        return $user->id === $attempt->user_id;
    }
}
