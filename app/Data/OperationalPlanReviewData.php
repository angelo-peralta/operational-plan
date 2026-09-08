<?php

namespace App\Data;

use App\Enums\OrganizationalUnitType;
use App\Enums\RemarkStatus;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class OperationalPlanReviewData
{
    /** @return array<string, mixed> */
    public static function forPlan(OperationalPlan $operationalPlan, User $viewer): array
    {
        $remarks = $operationalPlan->remarks->map(function (Remark $remark) use ($viewer): array {
            return self::remark($remark, $viewer);
        })->values();

        return [
            'counts' => [
                'total' => $remarks->count(),
                'open' => $remarks->where('status', RemarkStatus::Open->value)->count(),
                'addressed' => $remarks->where('status', RemarkStatus::Addressed->value)->count(),
                'resolved' => $remarks->where('status', RemarkStatus::Resolved->value)->count(),
                'president' => $remarks->where('author.authority', 'President')->count(),
                'vicePresident' => $remarks->where('author.authority', 'Vice President')->count(),
            ],
            'groups' => $remarks->groupBy(fn (array $remark): string => implode('|', [
                $remark['targetType'], $remark['targetId'], $remark['fieldKey'] ?? 'general', $remark['period']['id'] ?? 'all',
            ]))->map(fn ($group): array => ['label' => $group->first()['targetLabel'], 'anchor' => $group->first()['anchor'], 'count' => $group->count()])->values(),
            'remarks' => $remarks,
        ];
    }

    /** @return array<string, mixed> */
    public static function remark(Remark $remark, User $viewer): array
    {
        [$targetLabel, $anchor] = self::target($remark);

        return [
            'id' => $remark->id,
            'targetType' => $remark->remarkable_type,
            'targetId' => $remark->remarkable_id,
            'targetLabel' => $targetLabel,
            'anchor' => $anchor,
            'fieldKey' => $remark->field_key?->value,
            'period' => $remark->reportingPeriod ? ['id' => $remark->reportingPeriod->id, 'name' => $remark->reportingPeriod->name] : null,
            'category' => $remark->category->value,
            'content' => $remark->content,
            'status' => $remark->status->value,
            'statusLabel' => $remark->status->label(),
            'author' => ['id' => $remark->author->id, 'name' => $remark->author->name, 'authority' => self::authority($remark->author)],
            'createdAt' => $remark->created_at?->toISOString(),
            'addressedAt' => $remark->addressed_at?->toISOString(),
            'resolvedAt' => $remark->resolved_at?->toISOString(),
            'canAddress' => Gate::forUser($viewer)->allows('address', $remark),
            'canResolve' => Gate::forUser($viewer)->allows('resolve', $remark),
        ];
    }

    /** @return array{string, string} */
    private static function target(Remark $remark): array
    {
        if ($remark->remarkable instanceof OperationalPlan) {
            return [__('Operational Plan'), 'plan-overview'];
        }

        if ($remark->remarkable instanceof KeyResultArea) {
            return [$remark->remarkable->name, 'kra-'.$remark->remarkable->id];
        }

        if ($remark->remarkable instanceof PlanItem) {
            return [$remark->remarkable->objective, 'plan-item-'.$remark->remarkable->id];
        }

        if ($remark->remarkable instanceof Accomplishment) {
            return [__('Reported accomplishment'), 'accomplishment-'.$remark->remarkable->id];
        }

        if ($remark->remarkable instanceof Evaluation) {
            return [
                __('Evaluation by :name', ['name' => $remark->remarkable->reviewer->name]),
                'evaluation-'.$remark->remarkable->id,
            ];
        }

        $evidence = $remark->remarkable;

        return [
            $evidence->title ?: $evidence->original_filename,
            'evidence-'.$evidence->id,
        ];
    }

    private static function authority(User $author): string
    {
        $isPresident = $author->organizationalUnitAssignments->contains(
            fn ($assignment): bool => $assignment->organizationalUnit->type === OrganizationalUnitType::University,
        );

        return $isPresident ? __('President') : __('Vice President');
    }
}
