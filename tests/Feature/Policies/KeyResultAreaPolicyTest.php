<?php

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('key result area permissions follow the parent operational plan edit boundary', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $otherDepartmentUser = User::factory()->departmentUser()->forDepartment($otherDepartment)->create();
    $draftPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $submittedPlan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for(Department::factory())->create();
    $keyResultArea = KeyResultArea::factory()->for($draftPlan)->create();

    expect([
        'administrator.view' => Gate::forUser($administrator)->allows('view', $keyResultArea),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $keyResultArea),
        'departmentUser.view' => Gate::forUser($departmentUser)->allows('view', $keyResultArea),
        'otherDepartmentUser.view' => Gate::forUser($otherDepartmentUser)->allows('view', $keyResultArea),
        'administrator.createDraft' => Gate::forUser($administrator)->allows('create', [KeyResultArea::class, $draftPlan]),
        'departmentUser.createDraft' => Gate::forUser($departmentUser)->allows('create', [KeyResultArea::class, $draftPlan]),
        'reviewer.createDraft' => Gate::forUser($reviewer)->allows('create', [KeyResultArea::class, $draftPlan]),
        'administrator.createSubmitted' => Gate::forUser($administrator)->allows('create', [KeyResultArea::class, $submittedPlan]),
        'departmentUser.updateOwn' => Gate::forUser($departmentUser)->allows('update', $keyResultArea),
        'otherDepartmentUser.updateOther' => Gate::forUser($otherDepartmentUser)->allows('update', $keyResultArea),
        'departmentUser.reorderOwn' => Gate::forUser($departmentUser)->allows('reorder', [KeyResultArea::class, $draftPlan]),
        'departmentUser.deleteOwn' => Gate::forUser($departmentUser)->allows('delete', $keyResultArea),
    ])->toBe([
        'administrator.view' => true,
        'reviewer.view' => true,
        'departmentUser.view' => true,
        'otherDepartmentUser.view' => false,
        'administrator.createDraft' => true,
        'departmentUser.createDraft' => true,
        'reviewer.createDraft' => false,
        'administrator.createSubmitted' => false,
        'departmentUser.updateOwn' => true,
        'otherDepartmentUser.updateOther' => false,
        'departmentUser.reorderOwn' => true,
        'departmentUser.deleteOwn' => false,
    ]);
});
