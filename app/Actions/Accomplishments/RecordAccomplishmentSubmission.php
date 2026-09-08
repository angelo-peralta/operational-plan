<?php

namespace App\Actions\Accomplishments;

use App\Enums\AccomplishmentStatus;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evidence;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Carbon\CarbonInterface;

class RecordAccomplishmentSubmission
{
    public function handle(
        Accomplishment $accomplishment,
        User $actor,
        AccomplishmentStatus $fromStatus,
        AccomplishmentStatus $toStatus,
        int $revision,
        CarbonInterface $submittedAt,
    ): AccomplishmentSubmission {
        $accomplishment->loadMissing([
            'planItem.keyResultArea.operationalPlan.academicYear:id,name',
            'planItem.keyResultArea.operationalPlan.organizationalUnit:id,name,code',
            'planItem.keyResultArea.operationalPlan.accountableUser:id,name',
            'planItem.coAccountableOrganizationalUnits:id,name,code',
            'reportingPeriod:id,academic_year_id,name,code,sequence',
            'creator:id,name',
            'editor:id,name',
        ]);

        $evidence = $accomplishment->evidence()
            ->with('uploader:id,name')
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        return $accomplishment->submissions()->create([
            'revision' => $revision,
            'submitted_by' => $actor->id,
            'from_status' => $fromStatus->value,
            'to_status' => $toStatus->value,
            'evidence_count' => $evidence->count(),
            'snapshot' => $this->snapshot($accomplishment, $actor, array_values($evidence->all())),
            'submitted_at' => $submittedAt,
        ]);
    }

    /**
     * @param  list<Evidence>  $evidence
     * @return array<string, mixed>
     */
    private function snapshot(Accomplishment $accomplishment, User $actor, array $evidence): array
    {
        $planItem = $accomplishment->planItem;
        $keyResultArea = $planItem->keyResultArea;
        $operationalPlan = $keyResultArea->operationalPlan;
        $reportingPeriod = $accomplishment->reportingPeriod;

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
            'operational_plan' => [
                'id' => $operationalPlan->id,
                'goal' => $operationalPlan->goal,
                'accountable_user' => $operationalPlan->accountableUser ? [
                    'id' => $operationalPlan->accountableUser->id,
                    'name' => $operationalPlan->accountableUser->name,
                ] : null,
                'accountable_name' => $operationalPlan->accountable_name,
                'accountable_position' => $operationalPlan->accountable_position,
            ],
            'key_result_area' => [
                'id' => $keyResultArea->id,
                'code' => $keyResultArea->code,
                'name' => $keyResultArea->name,
            ],
            'plan_item' => [
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
                'co_accountable_organizational_units' => $planItem->coAccountableOrganizationalUnits
                    ->map(fn (OrganizationalUnit $organizationalUnit): array => [
                        'id' => $organizationalUnit->id,
                        'name' => $organizationalUnit->name,
                        'code' => $organizationalUnit->code,
                    ])
                    ->values()
                    ->all(),
            ],
            'reporting_period' => [
                'id' => $reportingPeriod->id,
                'name' => $reportingPeriod->name,
                'code' => $reportingPeriod->code,
                'sequence' => $reportingPeriod->sequence,
            ],
            'accomplishment' => [
                'id' => $accomplishment->id,
                'reported_value' => $accomplishment->reported_value,
                'accomplishment_text' => $accomplishment->accomplishment_text,
                'percentage_accomplished' => $accomplishment->percentage_accomplished,
                'created_by' => $accomplishment->creator ? [
                    'id' => $accomplishment->creator->id,
                    'name' => $accomplishment->creator->name,
                ] : null,
                'updated_by' => $accomplishment->editor ? [
                    'id' => $accomplishment->editor->id,
                    'name' => $accomplishment->editor->name,
                ] : null,
            ],
            'submitted_by' => [
                'id' => $actor->id,
                'name' => $actor->name,
            ],
            'evidence' => array_map(
                fn (Evidence $item): array => [
                    'id' => $item->id,
                    'evidence_type' => $item->evidence_type,
                    'title' => $item->title,
                    'description' => $item->description,
                    'original_filename' => $item->original_filename,
                    'mime_type' => $item->mime_type,
                    'file_size' => $item->file_size,
                    'checksum' => $item->checksum,
                    'uploaded_by' => [
                        'id' => $item->uploader->id,
                        'name' => $item->uploader->name,
                    ],
                    'created_at' => $item->created_at?->toISOString(),
                ],
                $evidence,
            ),
        ];
    }
}
