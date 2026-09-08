<?php

namespace App\Data;

use App\Actions\Accomplishments\ResolveEffectiveEvaluation;
use App\Enums\EvaluationDecision;
use App\Enums\OrganizationalUnitType;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use Illuminate\Support\Collection;

class OfficialOperationalPlanData
{
    public function __construct(
        private ResolveEffectiveEvaluation $resolveEffectiveEvaluation,
        private RemarkReportData $remarkReport,
    ) {}

    /** @return array<string, mixed> */
    public function forPlan(OperationalPlan $operationalPlan): array
    {
        $itemResults = $operationalPlan->keyResultAreas
            ->flatMap->planItems
            ->map(fn (PlanItem $planItem): array => $this->planItemResults($planItem, $operationalPlan))
            ->values()
            ->all();

        return [
            'itemResults' => $itemResults,
            'executiveRemarks' => $this->remarkReport->forScope(
                $operationalPlan->academicYear,
                [$operationalPlan->organizational_unit_id],
            ),
        ];
    }

    /** @return array<string, mixed> */
    private function planItemResults(
        PlanItem $planItem,
        OperationalPlan $operationalPlan,
    ): array {
        $accomplishments = $planItem->accomplishments
            ->filter(fn (Accomplishment $accomplishment): bool => $accomplishment->reportingPeriod->academic_year_id === $operationalPlan->academic_year_id)
            ->sortBy(fn (Accomplishment $accomplishment): int => $accomplishment->reportingPeriod->sequence)
            ->values();

        return [
            'planItemId' => $planItem->id,
            'firstSemester' => $this->semesterResult($accomplishments, 1),
            'secondSemester' => $this->semesterResult($accomplishments, 2),
        ];
    }

    /** @param Collection<int, Accomplishment> $accomplishments
     * @return array<string, mixed>|null
     */
    private function semesterResult(Collection $accomplishments, int $sequence): ?array
    {
        $accomplishment = $accomplishments->first(
            fn (Accomplishment $candidate): bool => $candidate->reportingPeriod->sequence === $sequence,
        );

        if (! $accomplishment instanceof Accomplishment) {
            return null;
        }

        $evaluation = $this->resolveEffectiveEvaluation->handle($accomplishment);

        return [
            'reportingPeriod' => [
                'id' => $accomplishment->reportingPeriod->id,
                'name' => $accomplishment->reportingPeriod->name,
                'code' => $accomplishment->reportingPeriod->code,
            ],
            'status' => $accomplishment->status->value,
            'statusLabel' => $accomplishment->status->label(),
            'rating' => $evaluation?->rating?->value,
            'ratingLabel' => $evaluation?->rating?->label(),
            'remarks' => $evaluation instanceof Evaluation
                ? $this->effectiveRemarks($accomplishment, $evaluation)
                : null,
            'reviewer' => $evaluation instanceof Evaluation ? [
                'id' => $evaluation->reviewer->id,
                'name' => $evaluation->reviewer->name,
                'authority' => $evaluation->organizationalUnit?->type === OrganizationalUnitType::University
                    ? __('President')
                    : __('Vice President'),
            ] : null,
            'isPresidential' => $evaluation?->organizationalUnit?->type === OrganizationalUnitType::University,
            'isOverride' => $evaluation instanceof Evaluation && $evaluation->is_override,
        ];
    }

    private function effectiveRemarks(
        Accomplishment $accomplishment,
        Evaluation $effectiveEvaluation,
    ): ?string {
        if (filled($effectiveEvaluation->remarks)) {
            return $effectiveEvaluation->remarks;
        }

        $submission = $accomplishment->submissions->first();
        if (! $submission instanceof AccomplishmentSubmission) {
            return null;
        }

        return $submission->evaluations
            ->first(fn (Evaluation $evaluation): bool => $evaluation->id !== $effectiveEvaluation->id
                && $evaluation->decision === EvaluationDecision::Accepted
                && $evaluation->rating !== null
                && $evaluation->organizationalUnit?->type !== OrganizationalUnitType::University)
            ?->remarks;
    }
}
