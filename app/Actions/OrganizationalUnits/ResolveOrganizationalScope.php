<?php

namespace App\Actions\OrganizationalUnits;

use App\Enums\OrganizationalUnitType;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitUserAssignment;
use App\Models\User;

class ResolveOrganizationalScope
{
    /** @return list<int> */
    public function assignedUnitIds(User $user): array
    {
        $type = $user->accessLevel()->assignmentType();

        if ($type === null) {
            return [];
        }

        return array_values($user->organizationalUnitAssignments()
            ->where('assignment_type', $type->value)
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('starts_on')->orWhereDate('starts_on', '<=', today()))
            ->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>=', today()))
            ->get(['organizational_unit_id'])->map(fn (OrganizationalUnitUserAssignment $assignment): int => $assignment->organizational_unit_id)->values()->all());
    }

    /** @return list<int> */
    public function visibleUnitIds(User $user): array
    {
        if ($user->isSystemAdmin()) {
            return $this->allUnitIds();
        }

        $ids = $this->assignedUnitIds($user);

        if ($user->isExecutive() && $ids !== []) {
            return $this->allUnitIds();
        }

        return array_values(array_unique($ids));
    }

    /** @return list<int> */
    public function oversightUnitIds(User $user): array
    {
        $unitIds = $this->visibleUnitIds($user);

        if (! $user->isExecutive()) {
            return $unitIds;
        }

        return array_values(OrganizationalUnit::query()
            ->whereKey($unitIds)
            ->where('type', '<>', OrganizationalUnitType::University->value)
            ->pluck('id')
            ->map(fn (mixed $unitId): int => (int) $unitId)
            ->all());
    }

    /** @return list<int> */
    public function manageableUnitIds(User $user): array
    {
        $query = OrganizationalUnit::query()->where('is_active', true);

        if (! $user->isSystemAdmin()) {
            $query->whereKey($this->assignedUnitIds($user));
        }

        return array_values($query->get(['id'])->map(fn (OrganizationalUnit $unit): int => $unit->id)->values()->all());
    }

    public function canViewUnit(User $user, int $unitId): bool
    {
        return in_array($unitId, $this->visibleUnitIds($user), true);
    }

    public function canManageUnit(User $user, int $unitId): bool
    {
        return in_array($unitId, $this->manageableUnitIds($user), true);
    }

    public function canSubmitUnit(User $user, int $unitId): bool
    {
        return ($user->isOfficeHead() || $user->isExecutive()) && $this->canManageUnit($user, $unitId);
    }

    public function canReviewUnit(User $user, int $unitId): bool
    {
        if (! $user->isExecutive() || ! OrganizationalUnit::query()->whereKey($unitId)->where('is_active', true)->exists()) {
            return false;
        }

        $assignedIds = $this->assignedUnitIds($user);
        if (in_array($unitId, $assignedIds, true)) {
            return false;
        }

        foreach (OrganizationalUnit::query()->whereKey($assignedIds)->where('is_active', true)->get() as $unit) {
            if (in_array($unitId, $unit->descendantUnitIds(), true)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<int> */
    private function allUnitIds(): array
    {
        return array_values(OrganizationalUnit::query()
            ->pluck('id')
            ->map(fn (mixed $unitId): int => (int) $unitId)
            ->all());
    }
}
