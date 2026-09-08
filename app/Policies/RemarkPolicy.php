<?php

namespace App\Policies;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AccomplishmentStatus;
use App\Enums\RemarkStatus;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\OperationalPlan;
use App\Models\Remark;
use App\Models\User;

class RemarkPolicy
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin() || $this->scope->visibleUnitIds($user) !== [];
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Remark $remark): bool
    {
        return $this->scope->canViewUnit($user, $remark->operationalPlan->organizational_unit_id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, OperationalPlan $operationalPlan): bool
    {
        return $operationalPlan->academicYear->isOpen()
            && $this->scope->canReviewUnit($user, $operationalPlan->organizational_unit_id);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function address(User $user, Remark $remark): bool
    {
        return $remark->status === RemarkStatus::Open
            && $this->isAddressable($remark)
            && $this->scope->canManageUnit($user, $remark->operationalPlan->organizational_unit_id);
    }

    public function resolve(User $user, Remark $remark): bool
    {
        return $remark->status === RemarkStatus::Addressed
            && $remark->operationalPlan->academicYear->isOpen()
            && $this->scope->canReviewUnit($user, $remark->operationalPlan->organizational_unit_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Remark $remark): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Remark $remark): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Remark $remark): bool
    {
        return false;
    }

    private function isAddressable(Remark $remark): bool
    {
        $remarkable = $remark->remarkable;
        $accomplishment = match (true) {
            $remarkable instanceof Accomplishment => $remarkable,
            $remarkable instanceof Evidence => $remarkable->accomplishment,
            $remarkable instanceof Evaluation => $remarkable->submission->accomplishment,
            default => null,
        };

        if ($accomplishment instanceof Accomplishment) {
            if ($remarkable instanceof Evaluation) {
                return $remark->operationalPlan->academicYear->isOpen();
            }

            return $remark->operationalPlan->academicYear->isOpen()
                && $accomplishment->reportingPeriod->acceptsSubmissions()
                && $accomplishment->status === AccomplishmentStatus::Returned;
        }

        return $remark->operationalPlan->isEditable();
    }
}
