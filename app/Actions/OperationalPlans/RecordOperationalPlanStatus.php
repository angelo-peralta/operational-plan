<?php

namespace App\Actions\OperationalPlans;

use App\Enums\OperationalPlanStatus;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;

class RecordOperationalPlanStatus
{
    public function handle(
        OperationalPlan $operationalPlan,
        User $actor,
        ?OperationalPlanStatus $fromStatus,
        OperationalPlanStatus $toStatus,
        ?string $remarks = null,
    ): OperationalPlanStatusHistory {
        $operationalPlan->loadMissing([
            'academicYear:id,name',
            'department:id,name,code',
            'accountableUser:id,name',
            'keyResultAreas.planItems.coAccountableDepartments:id,name,code',
        ]);

        return $operationalPlan->statusHistories()->create([
            'actor_id' => $actor->id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
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
                $coAccountableDepartments = [];

                foreach ($planItem->coAccountableDepartments as $department) {
                    $coAccountableDepartments[] = [
                        'id' => $department->id,
                        'name' => $department->name,
                        'code' => $department->code,
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
                    'co_accountable_departments' => $coAccountableDepartments,
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
            'department' => [
                'id' => $operationalPlan->department->id,
                'name' => $operationalPlan->department->name,
                'code' => $operationalPlan->department->code,
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
}
