<?php

namespace App\Policies;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Models\OrganizationalUnit;
use App\Models\User;

class OrganizationalUnitPolicy
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function view(User $user, OrganizationalUnit $unit): bool
    {
        return $this->scope->canViewUnit($user, $unit->id);
    }

    public function create(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    public function update(User $user, OrganizationalUnit $unit): bool
    {
        return $user->isSystemAdmin();
    }

    public function delete(User $user, OrganizationalUnit $unit): bool
    {
        return false;
    }
}
