<?php

namespace App\Policies;

use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\OperationalPlan;
use App\Models\User;

class OperationalPlanPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin()
            || $user->isReviewer()
            || ($user->isDepartmentUser() && $user->department_id !== null);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->isSuperAdmin()
            || $user->isReviewer()
            || ($user->isDepartmentUser() && $user->department_id === $operationalPlan->department_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, AcademicYear $academicYear): bool
    {
        if (! $academicYear->isOpen()) {
            return false;
        }

        return $user->isSuperAdmin()
            || ($user->isDepartmentUser() && ($user->department?->isActive() ?? false));
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, OperationalPlan $operationalPlan): bool
    {
        if (! $operationalPlan->isEditable()) {
            return false;
        }

        return $user->isSuperAdmin()
            || ($user->isDepartmentUser() && $user->department_id === $operationalPlan->department_id);
    }

    public function submit(User $user, OperationalPlan $operationalPlan): bool
    {
        return $this->update($user, $operationalPlan);
    }

    public function approve(User $user, OperationalPlan $operationalPlan): bool
    {
        return ($user->isSuperAdmin() || $user->isReviewer())
            && $operationalPlan->academicYear->isOpen()
            && $operationalPlan->status === OperationalPlanStatus::Submitted;
    }

    public function returnForRevision(User $user, OperationalPlan $operationalPlan): bool
    {
        return $this->approve($user, $operationalPlan);
    }

    public function close(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->isSuperAdmin()
            && $operationalPlan->academicYear->isOpen()
            && $operationalPlan->status === OperationalPlanStatus::Approved;
    }

    public function reopen(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->isSuperAdmin()
            && $operationalPlan->academicYear->isOpen()
            && in_array($operationalPlan->status, [
                OperationalPlanStatus::Approved,
                OperationalPlanStatus::Closed,
            ], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }
}
