<?php

namespace App\Policies;

use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function view(User $user, Evidence $evidence): bool
    {
        return $user->can('view', $evidence->accomplishment);
    }

    public function create(User $user, Accomplishment $accomplishment): bool
    {
        return $user->can('update', $accomplishment);
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        return false;
    }
}
