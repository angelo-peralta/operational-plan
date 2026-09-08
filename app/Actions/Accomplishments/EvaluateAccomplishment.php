<?php

namespace App\Actions\Accomplishments;

use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use App\Enums\OrganizationalUnitType;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class EvaluateAccomplishment
{
    public function __construct(
        private LockAccomplishmentForMutation $lockAccomplishment,
        private ResolveEvaluationAuthority $resolveAuthority,
    ) {}

    public function handle(
        Accomplishment $accomplishment,
        User $reviewer,
        EvaluationDecision $decision,
        ?EvaluationRating $rating,
        ?string $remarks,
        bool $isOverride = false,
    ): Evaluation {
        return DB::transaction(function () use ($accomplishment, $reviewer, $decision, $rating, $remarks, $isOverride): Evaluation {
            $context = $this->lockAccomplishment->handle(
                $accomplishment->plan_item_id,
                $accomplishment->reporting_period_id,
                $accomplishment->id,
            );
            $lockedAccomplishment = $context['accomplishment'];
            assert($lockedAccomplishment instanceof Accomplishment);

            Gate::forUser($reviewer)->authorize('create', [Evaluation::class, $lockedAccomplishment]);

            $submission = $lockedAccomplishment->submissions()
                ->lockForUpdate()
                ->first();

            if (! $submission instanceof AccomplishmentSubmission) {
                throw ValidationException::withMessages([
                    'accomplishment' => __('Immutable submission history is unavailable. This accomplishment cannot be evaluated.'),
                ]);
            }

            $evaluations = $submission->evaluations()
                ->with('organizationalUnit')
                ->lockForUpdate()
                ->get();

            if ($evaluations->contains('reviewer_id', $reviewer->id)) {
                throw ValidationException::withMessages([
                    'accomplishment' => __('You have already evaluated this submission revision.'),
                ]);
            }

            $targetUnit = $context['planItem']->keyResultArea->operationalPlan->organizationalUnit;
            $authorityUnit = $this->resolveAuthority->for($reviewer, $targetUnit);

            if (! $authorityUnit instanceof OrganizationalUnit) {
                throw ValidationException::withMessages([
                    'accomplishment' => __('Your active executive authority for this accomplishment is unavailable.'),
                ]);
            }

            $sourceEvaluation = $evaluations->first(
                fn (Evaluation $evaluation): bool => $evaluation->decision === EvaluationDecision::Accepted
                    && $evaluation->rating !== null
                    && $evaluation->organizationalUnit?->type !== OrganizationalUnitType::University,
            );
            $isPresidentialFollowUp = $this->resolveAuthority->isPresident($authorityUnit)
                && $sourceEvaluation instanceof Evaluation;

            $normalizedRemarks = is_string($remarks) && trim($remarks) !== ''
                ? trim($remarks)
                : null;

            $this->validateDecision($decision, $rating, $normalizedRemarks);
            $this->validatePresidentialReview(
                $isPresidentialFollowUp,
                $sourceEvaluation,
                $decision,
                $rating,
                $isOverride,
            );

            $reviewedAt = now();
            $evaluation = $submission->evaluations()->create([
                'reviewer_id' => $reviewer->id,
                'organizational_unit_id' => $authorityUnit->id,
                'decision' => $decision,
                'rating' => $rating,
                'remarks' => $normalizedRemarks,
                'is_override' => $isOverride,
                'overrides_evaluation_id' => $isOverride ? $sourceEvaluation?->id : null,
                'reviewed_at' => $reviewedAt,
                'created_at' => $reviewedAt,
            ]);

            $lockedAccomplishment->update([
                'status' => $decision->accomplishmentStatus(),
                'updated_by' => $reviewer->id,
            ]);

            return $evaluation->load([
                'reviewer:id,name',
                'organizationalUnit:id,name,code,type',
                'overriddenEvaluation',
                'submission',
            ]);
        });
    }

    private function validateDecision(
        EvaluationDecision $decision,
        ?EvaluationRating $rating,
        ?string $remarks,
    ): void {
        if ($decision->requiresRating() && $rating === null) {
            throw ValidationException::withMessages([
                'rating' => __('Select an official rating before accepting the accomplishment.'),
            ]);
        }

        if (! $decision->permitsRating() && $rating !== null) {
            throw ValidationException::withMessages([
                'rating' => __('A rating cannot be assigned when returning an accomplishment for revision.'),
            ]);
        }

        if ($decision->requiresRemarks($rating) && $remarks === null) {
            throw ValidationException::withMessages([
                'remarks' => __('Reviewer remarks are required for this decision or rating.'),
            ]);
        }
    }

    private function validatePresidentialReview(
        bool $isPresidentialFollowUp,
        ?Evaluation $sourceEvaluation,
        EvaluationDecision $decision,
        ?EvaluationRating $rating,
        bool $isOverride,
    ): void {
        if (! $isPresidentialFollowUp && $isOverride) {
            throw ValidationException::withMessages([
                'is_override' => __('Only a Presidential review of an existing VP evaluation can be an override.'),
            ]);
        }

        if (! $isPresidentialFollowUp) {
            return;
        }

        if ($decision !== EvaluationDecision::Accepted || $rating === null || ! $sourceEvaluation instanceof Evaluation || $sourceEvaluation->rating === null) {
            throw ValidationException::withMessages([
                'decision' => __('A Presidential follow-up must record agreement with or an override of the accepted VP rating.'),
            ]);
        }

        if (! $isOverride && $rating !== $sourceEvaluation->rating) {
            throw ValidationException::withMessages([
                'rating' => __('Choose the VP rating when recording agreement, or select the override option.'),
            ]);
        }

        if ($isOverride && $rating === $sourceEvaluation->rating) {
            throw ValidationException::withMessages([
                'rating' => __('An override must select a rating different from the VP rating.'),
            ]);
        }
    }
}
