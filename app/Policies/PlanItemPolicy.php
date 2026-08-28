<?php

namespace App\Policies;

use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\User;

class PlanItemPolicy
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
    public function view(User $user, PlanItem $planItem): bool
    {
        return $user->can('view', $planItem->keyResultArea->operationalPlan);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, KeyResultArea $keyResultArea): bool
    {
        return $user->can('update', $keyResultArea->operationalPlan);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlanItem $planItem): bool
    {
        return $user->can('update', $planItem->keyResultArea->operationalPlan);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlanItem $planItem): bool
    {
        return $this->update($user, $planItem);
    }

    public function reorder(User $user, KeyResultArea $keyResultArea): bool
    {
        return $user->can('update', $keyResultArea->operationalPlan);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlanItem $planItem): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlanItem $planItem): bool
    {
        return false;
    }
}
