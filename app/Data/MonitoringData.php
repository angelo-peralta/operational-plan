<?php

namespace App\Data;

use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
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
            'department' => [
                'id' => $operationalPlan->department->id,
                'name' => $operationalPlan->department->name,
                'code' => $operationalPlan->department->code,
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
                ? self::accomplishment($accomplishment)
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
    private static function accomplishment(Accomplishment $accomplishment): array
    {
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
        ];
    }
}
