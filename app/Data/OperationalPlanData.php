<?php

namespace App\Data;

use App\Enums\OperationalPlanStatus;
use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\OrganizationalUnit;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

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
            'organizationalUnit' => self::organizationalUnit($operationalPlan->organizationalUnit),
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
    public static function detail(
        OperationalPlan $operationalPlan,
        ?ReportingPeriod $reportingPeriod = null,
        ?User $user = null,
    ): array {
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
                        ->map(fn (PlanItem $planItem): array => self::planItem($planItem, $reportingPeriod, $user))
                        ->values(),
                ])
                ->values(),
            'statusHistory' => $operationalPlan->statusHistories
                ->map(fn (OperationalPlanStatusHistory $history): array => [
                    'id' => $history->id,
                    'fromStatus' => $history->from_status?->value,
                    'toStatus' => $history->to_status->value,
                    'toStatusLabel' => $history->to_status->label(),
                    'action' => $history->action ?? self::historyAction($history),
                    'actorRole' => $history->actor_role,
                    'actorOrganizationalUnitIds' => $history->actor_organizational_unit_ids ?? [],
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
    private static function planItem(
        PlanItem $planItem,
        ?ReportingPeriod $reportingPeriod,
        ?User $user,
    ): array {
        $accomplishment = $planItem->accomplishments->first();

        if ($accomplishment instanceof Accomplishment && $reportingPeriod instanceof ReportingPeriod) {
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
            'resourcesNeeded' => $planItem->resources_needed,
            'documentaryEvidenceRequirements' => $planItem->documentary_evidence_requirements ?? [],
            'manualCoAccountableUnits' => $planItem->manual_co_accountable_units ?? [],
            'coAccountableOrganizationalUnits' => $planItem->coAccountableOrganizationalUnits
                ->map(fn (OrganizationalUnit $organizationalUnit): array => self::organizationalUnit($organizationalUnit))
                ->values(),
            'sortOrder' => $planItem->sort_order,
            'accomplishment' => $accomplishment instanceof Accomplishment ? [
                'id' => $accomplishment->id,
                'reportedValue' => $accomplishment->reported_value,
                'accomplishmentText' => $accomplishment->accomplishment_text,
                'percentageAccomplished' => $accomplishment->percentage_accomplished,
                'status' => $accomplishment->status->value,
                'statusLabel' => $accomplishment->status->label(),
                'submittedAt' => $accomplishment->submitted_at?->toISOString(),
                'resubmittedAt' => $accomplishment->resubmitted_at?->toISOString(),
                'updatedAt' => $accomplishment->updated_at?->toISOString(),
                'createdBy' => null,
                'updatedBy' => null,
                'evidence' => [],
                'remarks' => [],
                'submissionBlocker' => null,
                'submissionHistory' => [],
                'evaluationHistory' => [],
                'permissions' => [
                    'uploadEvidence' => $user instanceof User
                        && Gate::forUser($user)->allows('create', [Evidence::class, $accomplishment]),
                    'submitAccomplishment' => false,
                    'createRemark' => false,
                ],
            ] : null,
            'permissions' => [
                'createAccomplishment' => $reportingPeriod instanceof ReportingPeriod
                    && $accomplishment === null
                    && $user instanceof User
                    && Gate::forUser($user)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
                'updateAccomplishment' => $accomplishment instanceof Accomplishment
                    && $user instanceof User
                    && Gate::forUser($user)->allows('update', $accomplishment),
            ],
        ];
    }

    /** @return array{id: int, name: string, code: string|null} */
    private static function organizationalUnit(OrganizationalUnit $organizationalUnit): array
    {
        return [
            'id' => $organizationalUnit->id,
            'name' => $organizationalUnit->name,
            'code' => $organizationalUnit->code,
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

    private static function historyAction(OperationalPlanStatusHistory $history): string
    {
        if ($history->to_status === OperationalPlanStatus::Returned && in_array($history->from_status, [OperationalPlanStatus::Approved, OperationalPlanStatus::Closed], true)) {
            return 'reopened';
        }

        return $history->to_status->value;
    }
}
