<?php

namespace App\Data;

use App\Enums\OperationalPlanStatus;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\PlanItem;

class OperationalPlanData
{
    /** @return array<string, mixed> */
    public static function summary(OperationalPlan $operationalPlan): array
    {
        return [
            'id' => $operationalPlan->id,
            'academicYear' => [
                'id' => $operationalPlan->academicYear->id,
                'name' => $operationalPlan->academicYear->name,
                'status' => $operationalPlan->academicYear->status->value,
                'isCurrent' => $operationalPlan->academicYear->is_current,
                'startYear' => $operationalPlan->academicYear->start_year,
                'endYear' => $operationalPlan->academicYear->end_year,
            ],
            'department' => self::department($operationalPlan->department),
            'accountableUser' => $operationalPlan->accountableUser ? [
                'id' => $operationalPlan->accountableUser->id,
                'name' => $operationalPlan->accountableUser->name,
            ] : null,
            'accountableName' => $operationalPlan->accountable_name,
            'accountablePosition' => $operationalPlan->accountable_position,
            'goal' => $operationalPlan->goal,
            'status' => $operationalPlan->status->value,
            'statusLabel' => $operationalPlan->status->label(),
            'submittedAt' => $operationalPlan->submitted_at?->toISOString(),
            'approvedAt' => $operationalPlan->approved_at?->toISOString(),
            'returnedAt' => $operationalPlan->returned_at?->toISOString(),
            'closedAt' => $operationalPlan->closed_at?->toISOString(),
            'keyResultAreaCount' => (int) $operationalPlan->getAttribute('key_result_areas_count'),
            'planItemCount' => (int) $operationalPlan->getAttribute('plan_items_count'),
            'latestReturnRemarks' => self::latestReturnRemarks($operationalPlan),
        ];
    }

    /** @return array<string, mixed> */
    public static function detail(OperationalPlan $operationalPlan): array
    {
        return [
            ...self::summary($operationalPlan),
            'keyResultAreas' => $operationalPlan->keyResultAreas
                ->map(fn (KeyResultArea $keyResultArea): array => [
                    'id' => $keyResultArea->id,
                    'code' => $keyResultArea->code,
                    'name' => $keyResultArea->name,
                    'description' => $keyResultArea->description,
                    'sortOrder' => $keyResultArea->sort_order,
                    'planItems' => $keyResultArea->planItems
                        ->map(fn (PlanItem $planItem): array => self::planItem($planItem))
                        ->values(),
                ])
                ->values(),
            'statusHistory' => $operationalPlan->statusHistories
                ->map(fn (OperationalPlanStatusHistory $history): array => [
                    'id' => $history->id,
                    'fromStatus' => $history->from_status?->value,
                    'toStatus' => $history->to_status->value,
                    'toStatusLabel' => $history->to_status->label(),
                    'remarks' => $history->remarks,
                    'actor' => [
                        'id' => $history->actor->id,
                        'name' => $history->actor->name,
                    ],
                    'createdAt' => $history->created_at->toISOString(),
                ])
                ->values(),
        ];
    }

    /** @return array<string, mixed> */
    private static function planItem(PlanItem $planItem): array
    {
        return [
            'id' => $planItem->id,
            'objective' => $planItem->objective,
            'strategy' => $planItem->strategy,
            'kpiTargetText' => $planItem->kpi_target_text,
            'targetValue' => $planItem->target_value,
            'targetUnit' => $planItem->target_unit,
            'targetOperator' => $planItem->target_operator?->value,
            'targetFrequency' => $planItem->target_frequency,
            'resourcesNeeded' => $planItem->resources_needed,
            'documentaryEvidenceRequirements' => $planItem->documentary_evidence_requirements ?? [],
            'manualCoAccountableUnits' => $planItem->manual_co_accountable_units ?? [],
            'coAccountableDepartments' => $planItem->coAccountableDepartments
                ->map(fn (Department $department): array => self::department($department))
                ->values(),
            'sortOrder' => $planItem->sort_order,
        ];
    }

    /** @return array{id: int, name: string, code: string|null} */
    private static function department(Department $department): array
    {
        return [
            'id' => $department->id,
            'name' => $department->name,
            'code' => $department->code,
        ];
    }

    private static function latestReturnRemarks(OperationalPlan $operationalPlan): ?string
    {
        if (! $operationalPlan->relationLoaded('statusHistories')) {
            return null;
        }

        return $operationalPlan->statusHistories
            ->first(fn (OperationalPlanStatusHistory $history): bool => $history->to_status === OperationalPlanStatus::Returned)
            ?->remarks;
    }
}
