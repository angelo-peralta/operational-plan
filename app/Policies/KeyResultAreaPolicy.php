<?php

namespace App\Policies;

use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;

class KeyResultAreaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', OperationalPlan::class);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, KeyResultArea $keyResultArea): bool
    {
        return $user->can('view', $keyResultArea->operationalPlan);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->can('update', $operationalPlan);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, KeyResultArea $keyResultArea): bool
    {
        return $user->can('update', $keyResultArea->operationalPlan);
    }

    public function reorder(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->can('update', $operationalPlan);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, KeyResultArea $keyResultArea): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, KeyResultArea $keyResultArea): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, KeyResultArea $keyResultArea): bool
    {
        return false;
    }
}
