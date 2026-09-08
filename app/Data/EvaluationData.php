<?php

namespace App\Data;

use App\Actions\Accomplishments\ResolveEffectiveEvaluation;
use App\Enums\EvaluationDecision;
use App\Enums\OrganizationalUnitType;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class EvaluationData
{
    /** @return array<string, mixed> */
    public static function queueItem(Accomplishment $accomplishment, User $user): array
    {
        $submission = $accomplishment->submissions->first();

        if (! $submission instanceof AccomplishmentSubmission) {
            throw new \LogicException('A review queue item requires an immutable submission revision.');
        }

        $snapshot = $submission->snapshot;
        $effectiveEvaluation = app(ResolveEffectiveEvaluation::class)->handle($accomplishment);
        $sourceEvaluation = $submission->evaluations->first(
            fn (Evaluation $evaluation): bool => $evaluation->decision === EvaluationDecision::Accepted
                && $evaluation->rating !== null
                && $evaluation->organizationalUnit?->type !== OrganizationalUnitType::University,
        );

        return [
            'id' => $accomplishment->id,
            'academicYear' => [
                'id' => (int) data_get($snapshot, 'academic_year.id'),
                'name' => (string) data_get($snapshot, 'academic_year.name'),
            ],
            'reportingPeriod' => [
                'id' => (int) data_get($snapshot, 'reporting_period.id'),
                'name' => (string) data_get($snapshot, 'reporting_period.name'),
                'code' => (string) data_get($snapshot, 'reporting_period.code'),
            ],
            'organizationalUnit' => [
                'id' => (int) (data_get($snapshot, 'organizational_unit.id') ?? data_get($snapshot, 'department.id')),
                'name' => (string) (data_get($snapshot, 'organizational_unit.name') ?? data_get($snapshot, 'department.name')),
                'code' => self::nullableString(data_get($snapshot, 'organizational_unit.code') ?? data_get($snapshot, 'department.code')),
            ],
            'operationalPlan' => [
                'id' => (int) data_get($snapshot, 'operational_plan.id'),
                'goal' => (string) data_get($snapshot, 'operational_plan.goal'),
                'accountableName' => self::nullableString(data_get($snapshot, 'operational_plan.accountable_name')),
                'accountablePosition' => self::nullableString(data_get($snapshot, 'operational_plan.accountable_position')),
            ],
            'keyResultArea' => [
                'id' => (int) data_get($snapshot, 'key_result_area.id'),
                'code' => self::nullableString(data_get($snapshot, 'key_result_area.code')),
                'name' => (string) data_get($snapshot, 'key_result_area.name'),
            ],
            'planItem' => [
                'id' => (int) data_get($snapshot, 'plan_item.id'),
                'objective' => (string) data_get($snapshot, 'plan_item.objective'),
                'strategy' => self::nullableString(data_get($snapshot, 'plan_item.strategy')),
                'kpiTargetText' => (string) data_get($snapshot, 'plan_item.kpi_target_text'),
                'targetValue' => self::nullableString(data_get($snapshot, 'plan_item.target_value')),
                'targetUnit' => self::nullableString(data_get($snapshot, 'plan_item.target_unit')),
                'targetOperator' => self::nullableString(data_get($snapshot, 'plan_item.target_operator')),
                'targetFrequency' => self::nullableString(data_get($snapshot, 'plan_item.target_frequency')),
                'resourcesNeeded' => self::nullableString(data_get($snapshot, 'plan_item.resources_needed')),
                'documentaryEvidenceRequirements' => self::stringList(
                    data_get($snapshot, 'plan_item.documentary_evidence_requirements'),
                ),
            ],
            'accomplishment' => [
                'id' => $accomplishment->id,
                'reportedValue' => self::nullableString(data_get($snapshot, 'accomplishment.reported_value')),
                'accomplishmentText' => self::nullableString(data_get($snapshot, 'accomplishment.accomplishment_text')),
                'percentageAccomplished' => self::nullableString(data_get($snapshot, 'accomplishment.percentage_accomplished')),
                'status' => $accomplishment->status->value,
                'statusLabel' => $accomplishment->status->label(),
            ],
            'submission' => [
                'id' => $submission->id,
                'revision' => $submission->revision,
                'submittedAt' => $submission->submitted_at->toISOString(),
                'submitter' => [
                    'id' => $submission->submitter->id,
                    'name' => $submission->submitter->name,
                ],
            ],
            'evidence' => self::evidence($snapshot),
            'remarks' => self::reviewRemarks($accomplishment, $user),
            'evaluationHistory' => self::history($accomplishment, $effectiveEvaluation),
            'effectiveEvaluation' => $effectiveEvaluation instanceof Evaluation
                ? self::effectiveEvaluation($effectiveEvaluation)
                : null,
            'presidentialReview' => $sourceEvaluation instanceof Evaluation
                && Gate::forUser($user)->allows('create', [Evaluation::class, $accomplishment])
                && $accomplishment->status->value === 'accepted'
                    ? self::presidentialReview($sourceEvaluation)
                    : null,
            'permissions' => [
                'reviewAccomplishment' => Gate::forUser($user)->allows('create', [
                    Evaluation::class,
                    $accomplishment,
                ]),
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function history(
        Accomplishment $accomplishment,
        ?Evaluation $effectiveEvaluation = null,
    ): array {
        $effectiveEvaluation ??= app(ResolveEffectiveEvaluation::class)->handle($accomplishment);
        $history = [];

        foreach ($accomplishment->submissions as $submission) {
            $evaluations = $submission->relationLoaded('evaluations')
                ? $submission->evaluations
                : $submission->evaluations()->with(['reviewer:id,name', 'organizationalUnit:id,name,code,type'])->get();

            foreach ($evaluations as $evaluation) {
                $history[] = self::historyEntry(
                    $evaluation,
                    $submission,
                    $effectiveEvaluation?->is($evaluation) ?? false,
                );
            }
        }

        return $history;
    }

    /** @return array<string, mixed> */
    private static function historyEntry(
        Evaluation $evaluation,
        AccomplishmentSubmission $submission,
        bool $isEffective,
    ): array {
        return [
            'id' => $evaluation->id,
            'submissionRevision' => $submission->revision,
            'decision' => $evaluation->decision->value,
            'decisionLabel' => $evaluation->decision->label(),
            'rating' => $evaluation->rating?->value,
            'ratingLabel' => $evaluation->rating?->label(),
            'remarks' => $evaluation->remarks,
            'resultingStatus' => $evaluation->decision->accomplishmentStatus()->value,
            'resultingStatusLabel' => $evaluation->decision->accomplishmentStatus()->label(),
            'reviewer' => [
                'id' => $evaluation->reviewer->id,
                'name' => $evaluation->reviewer->name,
            ],
            'authority' => self::authority($evaluation),
            'isOverride' => $evaluation->is_override,
            'overridesEvaluationId' => $evaluation->overrides_evaluation_id,
            'isEffective' => $isEffective,
            'reviewedAt' => $evaluation->reviewed_at->toISOString(),
        ];
    }

    /** @return array<string, mixed> */
    private static function effectiveEvaluation(Evaluation $evaluation): array
    {
        return [
            'id' => $evaluation->id,
            'rating' => $evaluation->rating?->value,
            'ratingLabel' => $evaluation->rating?->label(),
            'reviewer' => [
                'id' => $evaluation->reviewer->id,
                'name' => $evaluation->reviewer->name,
            ],
            'authority' => self::authority($evaluation),
            'isOverride' => $evaluation->is_override,
            'overridesEvaluationId' => $evaluation->overrides_evaluation_id,
        ];
    }

    /** @return array<string, mixed> */
    private static function presidentialReview(Evaluation $sourceEvaluation): array
    {
        return [
            'sourceEvaluationId' => $sourceEvaluation->id,
            'rating' => $sourceEvaluation->rating?->value,
            'ratingLabel' => $sourceEvaluation->rating?->label(),
            'remarks' => $sourceEvaluation->remarks,
            'reviewer' => [
                'id' => $sourceEvaluation->reviewer->id,
                'name' => $sourceEvaluation->reviewer->name,
            ],
            'authority' => self::authority($sourceEvaluation),
        ];
    }

    /** @return array{id: int|null, name: string|null, label: string} */
    private static function authority(Evaluation $evaluation): array
    {
        $unit = $evaluation->organizationalUnit;

        return [
            'id' => $unit?->id,
            'name' => $unit?->name,
            'label' => match ($unit?->type) {
                OrganizationalUnitType::University => __('President'),
                OrganizationalUnitType::VicePresidentialArea => __('Vice President'),
                null => __('Executive (legacy authority unavailable)'),
                default => __('Executive'),
            },
        ];
    }

    /** @return list<array<string, mixed>> */
    private static function reviewRemarks(Accomplishment $accomplishment, User $viewer): array
    {
        $operationalPlan = $accomplishment->planItem->keyResultArea->operationalPlan;
        $remarks = $accomplishment->remarks;

        $accomplishment->remarks->each(function (Remark $remark) use ($accomplishment, $operationalPlan): void {
            $remark->setRelation('remarkable', $accomplishment);
            $remark->setRelation('operationalPlan', $operationalPlan);
        });

        foreach ($accomplishment->evidence as $evidence) {
            $evidence->setRelation('accomplishment', $accomplishment);
            $evidence->remarks->each(function (Remark $remark) use ($evidence, $operationalPlan): void {
                $remark->setRelation('remarkable', $evidence);
                $remark->setRelation('operationalPlan', $operationalPlan);
            });
            $remarks = $remarks->concat($evidence->remarks);
        }

        foreach ($accomplishment->submissions as $submission) {
            foreach ($submission->evaluations as $evaluation) {
                $evaluation->setRelation('submission', $submission);
                $evaluation->fieldRemarks->each(function (Remark $remark) use ($evaluation, $operationalPlan): void {
                    $remark->setRelation('remarkable', $evaluation);
                    $remark->setRelation('operationalPlan', $operationalPlan);
                });
                $remarks = $remarks->concat($evaluation->fieldRemarks);
            }
        }

        return array_values($remarks
            ->sortBy(fn (Remark $remark): string => $remark->created_at?->toISOString() ?? '')
            ->map(fn (Remark $remark): array => OperationalPlanReviewData::remark($remark, $viewer))
            ->values()
            ->all());
    }

    /** @param array<string, mixed> $snapshot
     * @return list<array<string, mixed>>
     */
    private static function evidence(array $snapshot): array
    {
        $snapshotEvidence = data_get($snapshot, 'evidence');

        if (! is_array($snapshotEvidence)) {
            return [];
        }

        $evidence = [];

        foreach ($snapshotEvidence as $item) {
            if (! is_array($item)) {
                continue;
            }

            $evidence[] = [
                'id' => (int) data_get($item, 'id'),
                'evidenceType' => self::nullableString(data_get($item, 'evidence_type')),
                'title' => self::nullableString(data_get($item, 'title')),
                'description' => self::nullableString(data_get($item, 'description')),
                'originalFilename' => (string) data_get($item, 'original_filename'),
                'mimeType' => (string) data_get($item, 'mime_type'),
                'fileSize' => (int) data_get($item, 'file_size'),
                'checksum' => self::nullableString(data_get($item, 'checksum')),
                'uploadedBy' => [
                    'id' => (int) data_get($item, 'uploaded_by.id'),
                    'name' => (string) data_get($item, 'uploaded_by.name'),
                ],
                'createdAt' => self::nullableString(data_get($item, 'created_at')),
            ];
        }

        return $evidence;
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
