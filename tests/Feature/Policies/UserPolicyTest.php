<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('user management permissions match the system role matrix', function () {
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->create();
    $otherUser = User::factory()->departmentUser()->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', User::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', User::class),
        'departmentUser.viewAny' => Gate::forUser($departmentUser)->allows('viewAny', User::class),
        'administrator.viewOther' => Gate::forUser($administrator)->allows('view', $otherUser),
        'reviewer.viewSelf' => Gate::forUser($reviewer)->allows('view', $reviewer),
        'reviewer.viewOther' => Gate::forUser($reviewer)->allows('view', $otherUser),
        'departmentUser.viewSelf' => Gate::forUser($departmentUser)->allows('view', $departmentUser),
        'departmentUser.viewOther' => Gate::forUser($departmentUser)->allows('view', $otherUser),
        'administrator.create' => Gate::forUser($administrator)->allows('create', User::class),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', User::class),
        'departmentUser.create' => Gate::forUser($departmentUser)->allows('create', User::class),
        'administrator.update' => Gate::forUser($administrator)->allows('update', $otherUser),
        'reviewer.update' => Gate::forUser($reviewer)->allows('update', $reviewer),
        'departmentUser.update' => Gate::forUser($departmentUser)->allows('update', $departmentUser),
        'administrator.delete' => Gate::forUser($administrator)->allows('delete', $otherUser),
        'reviewer.delete' => Gate::forUser($reviewer)->allows('delete', $reviewer),
        'departmentUser.delete' => Gate::forUser($departmentUser)->allows('delete', $departmentUser),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => false,
        'departmentUser.viewAny' => false,
        'administrator.viewOther' => true,
        'reviewer.viewSelf' => true,
        'reviewer.viewOther' => false,
        'departmentUser.viewSelf' => true,
        'departmentUser.viewOther' => false,
        'administrator.create' => true,
        'reviewer.create' => false,
        'departmentUser.create' => false,
        'administrator.update' => true,
        'reviewer.update' => false,
        'departmentUser.update' => false,
        'administrator.delete' => false,
        'reviewer.delete' => false,
        'departmentUser.delete' => false,
    ]);
});
