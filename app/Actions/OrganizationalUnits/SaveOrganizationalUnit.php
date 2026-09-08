<?php

namespace App\Actions\OrganizationalUnits;

use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SaveOrganizationalUnit
{
    /** @param array<string, mixed> $data */
    public function handle(User $actor, array $data, ?OrganizationalUnit $unit = null): OrganizationalUnit
    {
        return DB::transaction(function () use ($actor, $data, $unit): OrganizationalUnit {
            $units = OrganizationalUnit::query()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $target = $unit === null ? new OrganizationalUnit : $units->get($unit->id);
            abort_unless($target instanceof OrganizationalUnit, 404);
            Gate::forUser($actor)->authorize($unit === null ? 'create' : 'update', $unit === null ? OrganizationalUnit::class : $target);

            $parentId = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
            $visited = [];
            while ($parentId !== null) {
                if ($parentId === $target->id || isset($visited[$parentId])) {
                    throw ValidationException::withMessages(['parent_id' => __('An organizational unit cannot be its own ancestor.')]);
                }
                $visited[$parentId] = true;
                $parent = $units->get($parentId);
                if ($parent === null) {
                    throw ValidationException::withMessages(['parent_id' => __('The selected parent unit no longer exists.')]);
                }
                $parentId = $parent->parent_id;
            }

            $target->fill($data)->save();

            return $target->refresh();
        });
    }
}
