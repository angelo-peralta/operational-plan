<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('operational role permissions match the system access matrix', function () {
    $role = Role::factory()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', Role::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', Role::class),
        'departmentUser.viewAny' => Gate::forUser($departmentUser)->allows('viewAny', Role::class),
        'administrator.view' => Gate::forUser($administrator)->allows('view', $role),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $role),
        'administrator.create' => Gate::forUser($administrator)->allows('create', Role::class),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', Role::class),
        'departmentUser.create' => Gate::forUser($departmentUser)->allows('create', Role::class),
        'administrator.update' => Gate::forUser($administrator)->allows('update', $role),
        'administrator.delete' => Gate::forUser($administrator)->allows('delete', $role),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => false,
        'departmentUser.viewAny' => false,
        'administrator.view' => true,
        'reviewer.view' => false,
        'administrator.create' => false,
        'reviewer.create' => false,
        'departmentUser.create' => false,
        'administrator.update' => false,
        'administrator.delete' => false,
    ]);
});

test('legacy role catalog links cannot elevate the canonical user role', function () {
    $customRole = Role::factory()->withAccessLevel(UserRole::SystemAdmin)->create([
        'name' => 'System Manager',
        'slug' => 'system-manager',
    ]);
    $user = User::factory()->forOperationalRole($customRole)->create();
    $user->forceFill(['role' => UserRole::Staff])->save();
    $user->unsetRelation('operationalRole');

    expect($user->isSystemAdmin())->toBeFalse()
        ->and($user->roleLabel())->toBe('Secretary / Staff')
        ->and(Gate::forUser($user)->allows('create', Role::class))->toBeFalse();
});
