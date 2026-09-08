<?php

namespace App\Policies;

use App\Actions\Accomplishments\ResolveEvaluationAuthority;
use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\OperationalPlanStatus;
use App\Enums\OrganizationalUnitType;
use App\Enums\ReportingPeriodStatus;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;
use App\Models\User;

class EvaluationPolicy
{
    public function __construct(
        private ResolveOrganizationalScope $scope,
        private ResolveEvaluationAuthority $resolveAuthority,
    ) {}

    /**
     * Determine whether the user can open the institutional review queue.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin() || ($user->isExecutive() && $this->scope->visibleUnitIds($user) !== []);
    }

    /**
     * Determine whether the user can view a stored evaluation.
     */
    public function view(User $user, Evaluation $evaluation): bool
    {
        $operationalPlan = $evaluation->submission
            ->accomplishment
            ->planItem
            ->keyResultArea
            ->operationalPlan;

        return $this->scope->canViewUnit($user, $operationalPlan->organizational_unit_id);
    }

    /**
     * Determine whether the user can evaluate this submitted accomplishment.
     */
    public function create(User $user, Accomplishment $accomplishment): bool
    {
        if (! $user->isExecutive()) {
            return false;
        }

        $operationalPlan = $accomplishment->planItem->keyResultArea->operationalPlan;
        $reportingPeriod = $accomplishment->reportingPeriod;

        if (! $this->scope->canReviewUnit($user, $operationalPlan->organizational_unit_id)
            || in_array($user->id, [$operationalPlan->created_by, $accomplishment->created_by, $accomplishment->submitted_by, $operationalPlan->accountable_user_id], true)
            || $operationalPlan->status !== OperationalPlanStatus::Approved
            || ! $operationalPlan->academicYear->isOpen()
            || $operationalPlan->academic_year_id !== $reportingPeriod->academic_year_id
            || $reportingPeriod->status === ReportingPeriodStatus::Draft
            || array_intersect(
                $this->scope->assignedUnitIds($user),
                $accomplishment->planItem->coAccountableOrganizationalUnits()->pluck('organizational_units.id')->all(),
            ) !== []) {
            return false;
        }

        $targetUnit = $operationalPlan->organizationalUnit;
        $authorityUnit = $this->resolveAuthority->for($user, $targetUnit);
        $submission = $accomplishment->submissions->first();

        if ($authorityUnit === null) {
            return false;
        }

        if (! $submission instanceof AccomplishmentSubmission) {
            return $accomplishment->status === AccomplishmentStatus::UnderReview
                && $this->resolveAuthority->isResponsibleInitialAuthority($user, $targetUnit);
        }

        $evaluations = $submission->relationLoaded('evaluations')
            ? $submission->evaluations
            : $submission->evaluations()->with('organizationalUnit')->get();

        if ($evaluations->contains('reviewer_id', $user->id)) {
            return false;
        }

        if ($accomplishment->status === AccomplishmentStatus::UnderReview) {
            return $evaluations->isEmpty()
                && $this->resolveAuthority->isResponsibleInitialAuthority($user, $targetUnit);
        }

        if ($accomplishment->status !== AccomplishmentStatus::Accepted
            || ! $this->resolveAuthority->isPresident($authorityUnit)) {
            return false;
        }

        $hasVpEvaluation = $evaluations->contains(
            fn (Evaluation $evaluation): bool => $evaluation->decision === EvaluationDecision::Accepted
                && $evaluation->rating !== null
                && $evaluation->organizationalUnit?->type !== OrganizationalUnitType::University,
        );
        $hasPresidentialEvaluation = $evaluations->contains(
            fn (Evaluation $evaluation): bool => $evaluation->organizationalUnit?->type === OrganizationalUnitType::University,
        );

        return $hasVpEvaluation && ! $hasPresidentialEvaluation;
    }

    public function update(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function delete(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function restore(User $user, Evaluation $evaluation): bool
    {
        return false;
    }

    public function forceDelete(User $user, Evaluation $evaluation): bool
    {
        return false;
    }
}
