<?php

namespace App\Policies;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        return $this->scope->canViewUnit($user, $department->id);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Department $department): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Department $department): bool
    {
        return false;
    }
}
