<?php

namespace App\Policies;

use App\Models\GigLead;
use App\Models\User;

class GigLeadPolicy
{
    public function update(User $user, GigLead $gigLead): bool
    {
        return $user->id === $gigLead->user_id;
    }

    public function delete(User $user, GigLead $gigLead): bool
    {
        return $user->id === $gigLead->user_id;
    }
}
