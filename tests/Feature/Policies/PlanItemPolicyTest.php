<?php

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('plan item permissions follow the parent operational plan edit boundary', function () {
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
    $submittedKeyResultArea = KeyResultArea::factory()->for($submittedPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();

    expect([
        'administrator.view' => Gate::forUser($administrator)->allows('view', $planItem),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $planItem),
        'departmentUser.view' => Gate::forUser($departmentUser)->allows('view', $planItem),
        'otherDepartmentUser.view' => Gate::forUser($otherDepartmentUser)->allows('view', $planItem),
        'administrator.createDraft' => Gate::forUser($administrator)->allows('create', [PlanItem::class, $keyResultArea]),
        'departmentUser.createDraft' => Gate::forUser($departmentUser)->allows('create', [PlanItem::class, $keyResultArea]),
        'reviewer.createDraft' => Gate::forUser($reviewer)->allows('create', [PlanItem::class, $keyResultArea]),
        'administrator.createSubmitted' => Gate::forUser($administrator)->allows('create', [PlanItem::class, $submittedKeyResultArea]),
        'departmentUser.updateOwn' => Gate::forUser($departmentUser)->allows('update', $planItem),
        'otherDepartmentUser.updateOther' => Gate::forUser($otherDepartmentUser)->allows('update', $planItem),
        'departmentUser.deleteOwn' => Gate::forUser($departmentUser)->allows('delete', $planItem),
        'departmentUser.reorderOwn' => Gate::forUser($departmentUser)->allows('reorder', [PlanItem::class, $keyResultArea]),
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
        'departmentUser.deleteOwn' => true,
        'departmentUser.reorderOwn' => true,
    ]);
});
