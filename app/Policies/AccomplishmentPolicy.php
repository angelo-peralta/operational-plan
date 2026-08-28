<?php

namespace App\Policies;

use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Models\Accomplishment;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;

class AccomplishmentPolicy
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
    public function view(User $user, Accomplishment $accomplishment): bool
    {
        $operationalPlan = $accomplishment->planItem->keyResultArea->operationalPlan;

        return $user->isSuperAdmin()
            || $user->isReviewer()
            || ($user->isDepartmentUser() && $user->department_id === $operationalPlan->department_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(
        User $user,
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
    ): bool {
        return $this->canAuthorDraft($user, $planItem, $reportingPeriod);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Accomplishment $accomplishment): bool
    {
        return $accomplishment->status === AccomplishmentStatus::Draft
            && $this->canAuthorDraft(
                $user,
                $accomplishment->planItem,
                $accomplishment->reportingPeriod,
            );
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Accomplishment $accomplishment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Accomplishment $accomplishment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Accomplishment $accomplishment): bool
    {
        return false;
    }

    private function canAuthorDraft(
        User $user,
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
    ): bool {
        if (! $user->isDepartmentUser()) {
            return false;
        }

        $operationalPlan = $planItem->keyResultArea->operationalPlan;

        return $user->department_id !== null
            && $user->department_id === $operationalPlan->department_id
            && $operationalPlan->academic_year_id === $reportingPeriod->academic_year_id
            && $operationalPlan->status === OperationalPlanStatus::Approved
            && $reportingPeriod->acceptsSubmissions();
    }
}
