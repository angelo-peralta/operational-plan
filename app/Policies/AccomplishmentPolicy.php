<?php

namespace App\Policies;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Models\Accomplishment;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;

class AccomplishmentPolicy
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin() || $this->scope->visibleUnitIds($user) !== [];
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Accomplishment $accomplishment): bool
    {
        $operationalPlan = $accomplishment->planItem->keyResultArea->operationalPlan;

        return $this->scope->canViewUnit($user, $operationalPlan->organizational_unit_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(
        User $user,
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
    ): bool {
        return $this->canAuthor($user, $planItem, $reportingPeriod);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Accomplishment $accomplishment): bool
    {
        return in_array($accomplishment->status, [
            AccomplishmentStatus::Draft,
            AccomplishmentStatus::Returned,
        ], true)
            && $this->canAuthor(
                $user,
                $accomplishment->planItem,
                $accomplishment->reportingPeriod,
            );
    }

    /**
     * Determine whether the user can submit or resubmit the accomplishment.
     */
    public function submit(User $user, Accomplishment $accomplishment): bool
    {
        return $this->scope->canSubmitUnit($user, $accomplishment->planItem->keyResultArea->operationalPlan->organizational_unit_id)
            && in_array($accomplishment->status, [
                AccomplishmentStatus::Draft,
                AccomplishmentStatus::Returned,
            ], true)
            && $this->canAuthor(
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

    private function canAuthor(
        User $user,
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
    ): bool {
        if ($user->isSystemAdmin()) {
            return false;
        }

        $operationalPlan = $planItem->keyResultArea->operationalPlan;

        return $this->scope->canManageUnit($user, $operationalPlan->organizational_unit_id)
            && $operationalPlan->academic_year_id === $reportingPeriod->academic_year_id
            && $operationalPlan->status === OperationalPlanStatus::Approved
            && $reportingPeriod->acceptsSubmissions();
    }
}
