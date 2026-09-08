<?php

namespace App\Actions\OperationalPlans;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\OperationalPlanStatus;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;

class RecordOperationalPlanStatus
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function handle(
        OperationalPlan $operationalPlan,
        User $actor,
        ?OperationalPlanStatus $fromStatus,
        OperationalPlanStatus $toStatus,
        ?string $remarks = null,
    ): OperationalPlanStatusHistory {
        $operationalPlan->loadMissing([
            'academicYear:id,name',
            'organizationalUnit:id,name,code',
            'accountableUser:id,name',
            'keyResultAreas.planItems.coAccountableOrganizationalUnits:id,name,code',
        ]);

        return $operationalPlan->statusHistories()->create([
            'actor_id' => $actor->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $this->action($fromStatus, $toStatus),
            'actor_role' => $actor->accessLevel()->value,
            'actor_organizational_unit_ids' => $this->scope->assignedUnitIds($actor),
            'remarks' => $remarks,
            'snapshot' => $this->snapshot($operationalPlan),
            'created_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function snapshot(OperationalPlan $operationalPlan): array
    {
        $keyResultAreas = [];

        foreach ($operationalPlan->keyResultAreas as $keyResultArea) {
            $planItems = [];

            foreach ($keyResultArea->planItems as $planItem) {
                $coAccountableOrganizationalUnits = [];

                foreach ($planItem->coAccountableOrganizationalUnits as $organizationalUnit) {
                    $coAccountableOrganizationalUnits[] = [
                        'id' => $organizationalUnit->id,
                        'name' => $organizationalUnit->name,
                        'code' => $organizationalUnit->code,
                    ];
                }

                $planItems[] = [
                    'id' => $planItem->id,
                    'objective' => $planItem->objective,
                    'strategy' => $planItem->strategy,
                    'kpi_target_text' => $planItem->kpi_target_text,
                    'target_value' => $planItem->target_value,
                    'target_unit' => $planItem->target_unit,
                    'target_operator' => $planItem->target_operator?->value,
                    'target_frequency' => $planItem->target_frequency,
                    'resources_needed' => $planItem->resources_needed,
                    'documentary_evidence_requirements' => $planItem->documentary_evidence_requirements,
                    'manual_co_accountable_units' => $planItem->manual_co_accountable_units,
                    'co_accountable_organizational_units' => $coAccountableOrganizationalUnits,
                    'sort_order' => $planItem->sort_order,
                ];
            }

            $keyResultAreas[] = [
                'id' => $keyResultArea->id,
                'code' => $keyResultArea->code,
                'name' => $keyResultArea->name,
                'description' => $keyResultArea->description,
                'sort_order' => $keyResultArea->sort_order,
                'plan_items' => $planItems,
            ];
        }

        return [
            'academic_year' => [
                'id' => $operationalPlan->academicYear->id,
                'name' => $operationalPlan->academicYear->name,
            ],
            'organizational_unit' => [
                'id' => $operationalPlan->organizationalUnit->id,
                'name' => $operationalPlan->organizationalUnit->name,
                'code' => $operationalPlan->organizationalUnit->code,
            ],
            'accountable_user' => $operationalPlan->accountableUser ? [
                'id' => $operationalPlan->accountableUser->id,
                'name' => $operationalPlan->accountableUser->name,
            ] : null,
            'accountable_name' => $operationalPlan->accountable_name,
            'accountable_position' => $operationalPlan->accountable_position,
            'goal' => $operationalPlan->goal,
            'key_result_areas' => $keyResultAreas,
        ];
    }

    private function action(?OperationalPlanStatus $fromStatus, OperationalPlanStatus $toStatus): string
    {
        if ($toStatus === OperationalPlanStatus::Returned && in_array($fromStatus, [OperationalPlanStatus::Approved, OperationalPlanStatus::Closed], true)) {
            return 'reopened';
        }

        return match ($toStatus) {
            OperationalPlanStatus::Draft => 'created',
            OperationalPlanStatus::Submitted => 'submitted',
            OperationalPlanStatus::Returned => 'returned',
            OperationalPlanStatus::Approved => 'approved',
            OperationalPlanStatus::Closed => 'closed',
        };
    }
}
