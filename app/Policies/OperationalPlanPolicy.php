<?php

namespace App\Policies;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\OperationalPlan;
use App\Models\User;

class OperationalPlanPolicy
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin() || $this->scope->visibleUnitIds($user) !== [];
    }

    public function view(User $user, OperationalPlan $operationalPlan): bool
    {
        return $this->scope->canViewUnit($user, $operationalPlan->organizational_unit_id);
    }

    public function create(User $user, AcademicYear $academicYear): bool
    {
        return $academicYear->isOpen() && $this->scope->manageableUnitIds($user) !== [];
    }

    public function update(User $user, OperationalPlan $operationalPlan): bool
    {
        return $operationalPlan->isEditable()
            && $this->scope->canManageUnit($user, $operationalPlan->organizational_unit_id);
    }

    public function submit(User $user, OperationalPlan $operationalPlan): bool
    {
        return $operationalPlan->isEditable()
            && $this->scope->canSubmitUnit($user, $operationalPlan->organizational_unit_id);
    }

    public function approve(User $user, OperationalPlan $operationalPlan): bool
    {
        return $this->scope->canReviewUnit($user, $operationalPlan->organizational_unit_id)
            && ! in_array($user->id, [$operationalPlan->created_by, $operationalPlan->submitted_by, $operationalPlan->accountable_user_id], true)
            && $operationalPlan->academicYear->isOpen()
            && $operationalPlan->status === OperationalPlanStatus::Submitted;
    }

    public function returnForRevision(User $user, OperationalPlan $operationalPlan): bool
    {
        return $this->approve($user, $operationalPlan);
    }

    public function close(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->isSystemAdmin()
            && $operationalPlan->academicYear->isOpen()
            && $operationalPlan->status === OperationalPlanStatus::Approved;
    }

    public function reopen(User $user, OperationalPlan $operationalPlan): bool
    {
        return $user->isSystemAdmin()
            && $operationalPlan->academicYear->isOpen()
            && in_array($operationalPlan->status, [OperationalPlanStatus::Approved, OperationalPlanStatus::Closed], true);
    }

    public function delete(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }

    public function restore(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }

    public function forceDelete(User $user, OperationalPlan $operationalPlan): bool
    {
        return false;
    }
}
