<?php

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('academic year permissions match the system role matrix', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', AcademicYear::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', AcademicYear::class),
        'departmentUser.viewAny' => Gate::forUser($departmentUser)->allows('viewAny', AcademicYear::class),
        'administrator.view' => Gate::forUser($administrator)->allows('view', $academicYear),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $academicYear),
        'departmentUser.view' => Gate::forUser($departmentUser)->allows('view', $academicYear),
        'administrator.create' => Gate::forUser($administrator)->allows('create', AcademicYear::class),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', AcademicYear::class),
        'departmentUser.create' => Gate::forUser($departmentUser)->allows('create', AcademicYear::class),
        'administrator.update' => Gate::forUser($administrator)->allows('update', $academicYear),
        'reviewer.update' => Gate::forUser($reviewer)->allows('update', $academicYear),
        'departmentUser.update' => Gate::forUser($departmentUser)->allows('update', $academicYear),
        'administrator.delete' => Gate::forUser($administrator)->allows('delete', $academicYear),
        'reviewer.delete' => Gate::forUser($reviewer)->allows('delete', $academicYear),
        'departmentUser.delete' => Gate::forUser($departmentUser)->allows('delete', $academicYear),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => true,
        'departmentUser.viewAny' => true,
        'administrator.view' => true,
        'reviewer.view' => true,
        'departmentUser.view' => true,
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
