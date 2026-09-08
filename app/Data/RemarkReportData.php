<?php

namespace App\Data;

use App\Enums\OrganizationalUnitType;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;

class RemarkReportData
{
    /** @param list<int> $unitIds
     * @return list<array<string, mixed>>
     */
    public function forScope(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod = null,
    ): array {
        return array_values(Remark::query()
            ->with([
                'operationalPlan.organizationalUnit:id,name,code',
                'author.organizationalUnitAssignments.organizationalUnit',
                'reportingPeriod:id,name,code',
                'remarkable' => function (Relation $relation): void {
                    if (! $relation instanceof MorphTo) {
                        return;
                    }

                    $relation->morphWith([
                        PlanItem::class => ['keyResultArea:id,operational_plan_id,code,name'],
                        Accomplishment::class => ['planItem.keyResultArea:id,operational_plan_id,code,name'],
                        Evidence::class => ['accomplishment.planItem.keyResultArea:id,operational_plan_id,code,name'],
                        Evaluation::class => [
                            'reviewer:id,name',
                            'submission.accomplishment.planItem.keyResultArea:id,operational_plan_id,code,name',
                        ],
                    ]);
                },
            ])
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('operationalPlan', fn ($query) => $query
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('organizational_unit_id', $unitIds))
            ->when($reportingPeriod, fn ($query) => $query->where(function ($query) use ($reportingPeriod): void {
                $query->whereNull('reporting_period_id')
                    ->orWhere('reporting_period_id', $reportingPeriod->id);
            }))
            ->latest('created_at')
            ->latest('id')
            ->get()
            ->map(fn (Remark $remark): array => $this->remark($remark))
            ->values()
            ->all());
    }

    /** @return array<string, mixed> */
    public function remark(Remark $remark): array
    {
        [$keyResultArea, $planItem, $targetLabel] = $this->targetContext($remark);

        return [
            'id' => $remark->id,
            'organizationalUnit' => [
                'id' => $remark->operationalPlan->organizationalUnit->id,
                'name' => $remark->operationalPlan->organizationalUnit->name,
                'code' => $remark->operationalPlan->organizationalUnit->code,
            ],
            'author' => [
                'id' => $remark->author->id,
                'name' => $remark->author->name,
                'authority' => $this->authority($remark->author),
            ],
            'targetType' => $remark->remarkable_type,
            'targetLabel' => $targetLabel,
            'keyResultArea' => $keyResultArea ? [
                'id' => $keyResultArea->id,
                'code' => $keyResultArea->code,
                'name' => $keyResultArea->name,
            ] : null,
            'planItem' => $planItem ? [
                'id' => $planItem->id,
                'objective' => $planItem->objective,
            ] : null,
            'field' => $remark->field_key?->value,
            'fieldLabel' => $remark->field_key
                ? str($remark->field_key->value)->replace('_', ' ')->title()->toString()
                : __('General'),
            'period' => $remark->reportingPeriod ? [
                'id' => $remark->reportingPeriod->id,
                'name' => $remark->reportingPeriod->name,
                'code' => $remark->reportingPeriod->code,
            ] : null,
            'content' => $remark->content,
            'status' => $remark->status->value,
            'statusLabel' => $remark->status->label(),
            'createdAt' => $remark->created_at?->toISOString(),
        ];
    }

    /** @return array{KeyResultArea|null, PlanItem|null, string} */
    private function targetContext(Remark $remark): array
    {
        $target = $remark->remarkable;

        if ($target instanceof OperationalPlan) {
            return [null, null, __('Operational Plan')];
        }

        if ($target instanceof KeyResultArea) {
            return [$target, null, $target->name];
        }

        if ($target instanceof PlanItem) {
            return [$target->keyResultArea, $target, $target->objective];
        }

        if ($target instanceof Accomplishment) {
            return [
                $target->planItem->keyResultArea,
                $target->planItem,
                __('Reported accomplishment'),
            ];
        }

        if ($target instanceof Evidence) {
            return [
                $target->accomplishment->planItem->keyResultArea,
                $target->accomplishment->planItem,
                $target->title ?: $target->original_filename,
            ];
        }

        $planItem = $target->submission->accomplishment->planItem;

        return [
            $planItem->keyResultArea,
            $planItem,
            __('Evaluation by :name', ['name' => $target->reviewer->name]),
        ];
    }

    private function authority(User $author): string
    {
        $isPresident = $author->organizationalUnitAssignments->contains(
            fn ($assignment): bool => $assignment->organizationalUnit->type === OrganizationalUnitType::University,
        );

        return $isPresident ? __('President') : __('Vice President');
    }
}
