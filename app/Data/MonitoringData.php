<?php

namespace App\Data;

use App\Enums\AccomplishmentStatus;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class MonitoringData
{
    /** @return array<string, mixed> */
    public static function academicYear(AcademicYear $academicYear): array
    {
        return [
            'id' => $academicYear->id,
            'name' => $academicYear->name,
            'status' => $academicYear->status->value,
            'isCurrent' => $academicYear->is_current,
            'startYear' => $academicYear->start_year,
            'endYear' => $academicYear->end_year,
        ];
    }

    /** @return array<string, mixed> */
    public static function reportingPeriod(ReportingPeriod $reportingPeriod): array
    {
        return [
            'id' => $reportingPeriod->id,
            'name' => $reportingPeriod->name,
            'code' => $reportingPeriod->code,
            'sequence' => $reportingPeriod->sequence,
            'startsOn' => $reportingPeriod->starts_on?->toDateString(),
            'endsOn' => $reportingPeriod->ends_on?->toDateString(),
            'status' => $reportingPeriod->status->value,
            'acceptsSubmissions' => $reportingPeriod->acceptsSubmissions(),
        ];
    }

    /** @return array<string, mixed> */
    public static function operationalPlan(
        OperationalPlan $operationalPlan,
        ReportingPeriod $reportingPeriod,
        User $user,
    ): array {
        return [
            'id' => $operationalPlan->id,
            'organizationalUnit' => [
                'id' => $operationalPlan->organizationalUnit->id,
                'name' => $operationalPlan->organizationalUnit->name,
                'code' => $operationalPlan->organizationalUnit->code,
            ],
            'goal' => $operationalPlan->goal,
            'status' => $operationalPlan->status->value,
            'statusLabel' => $operationalPlan->status->label(),
            'keyResultAreas' => $operationalPlan->keyResultAreas
                ->map(function (KeyResultArea $keyResultArea) use ($operationalPlan, $reportingPeriod, $user): array {
                    $keyResultArea->setRelation('operationalPlan', $operationalPlan);

                    return [
                        'id' => $keyResultArea->id,
                        'code' => $keyResultArea->code,
                        'name' => $keyResultArea->name,
                        'description' => $keyResultArea->description,
                        'sortOrder' => $keyResultArea->sort_order,
                        'planItems' => $keyResultArea->planItems
                            ->map(function (PlanItem $planItem) use ($keyResultArea, $reportingPeriod, $user): array {
                                $planItem->setRelation('keyResultArea', $keyResultArea);

                                return self::planItem($planItem, $reportingPeriod, $user);
                            })
                            ->values(),
                    ];
                })
                ->values(),
        ];
    }

    /** @return array<string, mixed> */
    private static function planItem(
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
        User $user,
    ): array {
        $accomplishment = $planItem->accomplishments->first();

        if ($accomplishment !== null) {
            $accomplishment->setRelation('planItem', $planItem);
            $accomplishment->setRelation('reportingPeriod', $reportingPeriod);
        }

        return [
            'id' => $planItem->id,
            'objective' => $planItem->objective,
            'strategy' => $planItem->strategy,
            'kpiTargetText' => $planItem->kpi_target_text,
            'targetValue' => $planItem->target_value,
            'targetUnit' => $planItem->target_unit,
            'targetOperator' => $planItem->target_operator?->value,
            'targetFrequency' => $planItem->target_frequency,
            'documentaryEvidenceRequirements' => $planItem->documentary_evidence_requirements ?? [],
            'sortOrder' => $planItem->sort_order,
            'accomplishment' => $accomplishment instanceof Accomplishment
                ? self::accomplishment($accomplishment, $user)
                : null,
            'permissions' => [
                'createAccomplishment' => $accomplishment === null
                    && Gate::forUser($user)->allows('create', [
                        Accomplishment::class,
                        $planItem,
                        $reportingPeriod,
                    ]),
                'updateAccomplishment' => $accomplishment instanceof Accomplishment
                    && Gate::forUser($user)->allows('update', $accomplishment),
            ],
        ];
    }

    /** @return array<string, mixed> */
    private static function accomplishment(Accomplishment $accomplishment, User $user): array
    {
        $canSubmit = Gate::forUser($user)->allows('submit', $accomplishment);
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

        return [
            'id' => $accomplishment->id,
            'reportedValue' => $accomplishment->reported_value,
            'accomplishmentText' => $accomplishment->accomplishment_text,
            'percentageAccomplished' => $accomplishment->percentage_accomplished,
            'status' => $accomplishment->status->value,
            'statusLabel' => $accomplishment->status->label(),
            'submittedAt' => $accomplishment->submitted_at?->toISOString(),
            'resubmittedAt' => $accomplishment->resubmitted_at?->toISOString(),
            'updatedAt' => $accomplishment->updated_at?->toISOString(),
            'createdBy' => $accomplishment->creator ? [
                'id' => $accomplishment->creator->id,
                'name' => $accomplishment->creator->name,
            ] : null,
            'updatedBy' => $accomplishment->editor ? [
                'id' => $accomplishment->editor->id,
                'name' => $accomplishment->editor->name,
            ] : null,
            'submissionBlocker' => $canSubmit
                ? self::submissionBlocker($accomplishment)
                : null,
            'submissionHistory' => $accomplishment->submissions
                ->map(fn (AccomplishmentSubmission $submission): array => [
                    'id' => $submission->id,
                    'revision' => $submission->revision,
                    'fromStatus' => $submission->from_status->value,
                    'toStatus' => $submission->to_status->value,
                    'toStatusLabel' => $submission->to_status->label(),
                    'evidenceCount' => $submission->evidence_count,
                    'submitter' => [
                        'id' => $submission->submitter->id,
                        'name' => $submission->submitter->name,
                    ],
                    'submittedAt' => $submission->submitted_at->toISOString(),
                ])
                ->values(),
            'evaluationHistory' => EvaluationData::history($accomplishment),
            'evidence' => $accomplishment->evidence
                ->map(fn (Evidence $evidence): array => [
                    'id' => $evidence->id,
                    'evidenceType' => $evidence->evidence_type,
                    'title' => $evidence->title,
                    'description' => $evidence->description,
                    'originalFilename' => $evidence->original_filename,
                    'mimeType' => $evidence->mime_type,
                    'fileSize' => $evidence->file_size,
                    'checksum' => $evidence->checksum,
                    'uploadedBy' => [
                        'id' => $evidence->uploader->id,
                        'name' => $evidence->uploader->name,
                    ],
                    'createdAt' => $evidence->created_at?->toISOString(),
                ])
                ->values(),
            'remarks' => $remarks
                ->sortBy(fn (Remark $remark): string => $remark->created_at?->toISOString() ?? '')
                ->map(fn (Remark $remark): array => OperationalPlanReviewData::remark($remark, $user))
                ->values(),
            'permissions' => [
                'uploadEvidence' => Gate::forUser($user)->allows('create', [Evidence::class, $accomplishment]),
                'submitAccomplishment' => $canSubmit,
                'createRemark' => Gate::forUser($user)->allows('create', [Remark::class, $operationalPlan]),
            ],
        ];
    }

    private static function submissionBlocker(Accomplishment $accomplishment): ?string
    {
        if (blank($accomplishment->reported_value) && blank($accomplishment->accomplishment_text)) {
            return __('Enter a reported value or accomplishment description before submitting.');
        }

        $evidenceCount = $accomplishment->evidence->count();

        if ($evidenceCount === 0) {
            return __('Upload at least one evidence file before submitting.');
        }

        if ($accomplishment->status !== AccomplishmentStatus::Returned) {
            return null;
        }

        $latestSubmission = $accomplishment->submissions->first();

        if (! $latestSubmission instanceof AccomplishmentSubmission) {
            return __('Previous submission history is unavailable. Contact an administrator before resubmitting.');
        }

        if ($evidenceCount <= $latestSubmission->evidence_count) {
            return __('Upload at least one additional evidence file before resubmitting.');
        }

        return null;
    }
}
