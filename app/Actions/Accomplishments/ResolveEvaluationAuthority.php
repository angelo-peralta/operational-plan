<?php

namespace App\Actions\Accomplishments;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AssignmentType;
use App\Enums\OrganizationalUnitType;
use App\Enums\UserRole;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitUserAssignment;
use App\Models\User;

class ResolveEvaluationAuthority
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function for(User $evaluator, OrganizationalUnit $targetUnit): ?OrganizationalUnit
    {
        $ancestorDistances = $this->ancestorDistances($targetUnit);
        $authorityUnitId = collect($this->scope->assignedUnitIds($evaluator))
            ->filter(fn (int $unitId): bool => isset($ancestorDistances[$unitId]))
            ->sortBy(fn (int $unitId): int => $ancestorDistances[$unitId])
            ->first();

        return is_int($authorityUnitId)
            ? OrganizationalUnit::query()->find($authorityUnitId)
            : null;
    }

    public function isResponsibleInitialAuthority(User $evaluator, OrganizationalUnit $targetUnit): bool
    {
        $authorityUnit = $this->for($evaluator, $targetUnit);

        return $authorityUnit instanceof OrganizationalUnit
            && in_array($authorityUnit->id, $this->responsibleAuthorityUnitIds($targetUnit), true);
    }

    public function isPresident(OrganizationalUnit $authorityUnit): bool
    {
        return $authorityUnit->type === OrganizationalUnitType::University;
    }

    /** @return list<int> */
    private function responsibleAuthorityUnitIds(OrganizationalUnit $targetUnit): array
    {
        $ancestorDistances = $this->ancestorDistances($targetUnit);

        if ($ancestorDistances === []) {
            return [];
        }

        $assignedUnitIds = OrganizationalUnitUserAssignment::query()
            ->whereIn('organizational_unit_id', array_keys($ancestorDistances))
            ->where('assignment_type', AssignmentType::Executive)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', today()))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->whereHas('organizationalUnit', fn ($query) => $query->where('is_active', true))
            ->whereHas('user', fn ($query) => $query->where('role', UserRole::Executive))
            ->distinct()
            ->pluck('organizational_unit_id')
            ->map(fn (mixed $unitId): int => (int) $unitId)
            ->all();

        if ($assignedUnitIds === []) {
            return [];
        }

        $closestDistance = min(array_map(
            fn (int $unitId): int => $ancestorDistances[$unitId],
            $assignedUnitIds,
        ));

        return array_values(array_filter(
            $assignedUnitIds,
            fn (int $unitId): bool => $ancestorDistances[$unitId] === $closestDistance,
        ));
    }

    /** @return array<int, int> */
    private function ancestorDistances(OrganizationalUnit $targetUnit): array
    {
        $parentIds = OrganizationalUnit::query()
            ->pluck('parent_id', 'id')
            ->mapWithKeys(fn (mixed $parentId, mixed $unitId): array => [(int) $unitId => $parentId === null ? null : (int) $parentId])
            ->all();
        $distances = [];
        $visited = [$targetUnit->id => true];
        $currentId = $parentIds[$targetUnit->id] ?? null;
        $distance = 1;

        while (is_int($currentId) && ! isset($visited[$currentId])) {
            $visited[$currentId] = true;
            $distances[$currentId] = $distance;
            $currentId = $parentIds[$currentId] ?? null;
            $distance++;
        }

        return $distances;
    }
}
