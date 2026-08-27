<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('department permissions enforce global and assigned-department access', function () {
    $assignedDepartment = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $assignedUser = User::factory()->departmentUser()->forDepartment($assignedDepartment)->create();
    $unassignedUser = User::factory()->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', Department::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', Department::class),
        'assignedUser.viewAny' => Gate::forUser($assignedUser)->allows('viewAny', Department::class),
        'administrator.viewOther' => Gate::forUser($administrator)->allows('view', $otherDepartment),
        'reviewer.viewOther' => Gate::forUser($reviewer)->allows('view', $otherDepartment),
        'assignedUser.viewOwn' => Gate::forUser($assignedUser)->allows('view', $assignedDepartment),
        'assignedUser.viewOther' => Gate::forUser($assignedUser)->allows('view', $otherDepartment),
        'unassignedUser.viewAssigned' => Gate::forUser($unassignedUser)->allows('view', $assignedDepartment),
        'administrator.create' => Gate::forUser($administrator)->allows('create', Department::class),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', Department::class),
        'assignedUser.create' => Gate::forUser($assignedUser)->allows('create', Department::class),
        'administrator.update' => Gate::forUser($administrator)->allows('update', $assignedDepartment),
        'reviewer.update' => Gate::forUser($reviewer)->allows('update', $assignedDepartment),
        'assignedUser.update' => Gate::forUser($assignedUser)->allows('update', $assignedDepartment),
        'administrator.delete' => Gate::forUser($administrator)->allows('delete', $assignedDepartment),
        'reviewer.delete' => Gate::forUser($reviewer)->allows('delete', $assignedDepartment),
        'assignedUser.delete' => Gate::forUser($assignedUser)->allows('delete', $assignedDepartment),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => true,
        'assignedUser.viewAny' => false,
        'administrator.viewOther' => true,
        'reviewer.viewOther' => true,
        'assignedUser.viewOwn' => true,
        'assignedUser.viewOther' => false,
        'unassignedUser.viewAssigned' => false,
        'administrator.create' => true,
        'reviewer.create' => false,
        'assignedUser.create' => false,
        'administrator.update' => true,
        'reviewer.update' => false,
        'assignedUser.update' => false,
        'administrator.delete' => false,
        'reviewer.delete' => false,
        'assignedUser.delete' => false,
    ]);
});
