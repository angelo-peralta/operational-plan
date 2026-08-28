<?php

use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('operational plan permissions enforce role department status and academic year boundaries', function () {
    $openAcademicYear = AcademicYear::factory()->open()->create();
    $closedAcademicYear = AcademicYear::factory()->closed()->create();
    $assignedDepartment = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $inactiveDepartment = Department::factory()->inactive()->create();
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $assignedUser = User::factory()->departmentUser()->forDepartment($assignedDepartment)->create();
    $otherDepartmentUser = User::factory()->departmentUser()->forDepartment($otherDepartment)->create();
    $inactiveDepartmentUser = User::factory()->departmentUser()->forDepartment($inactiveDepartment)->create();
    $unassignedDepartmentUser = User::factory()->create();
    $draftPlan = OperationalPlan::factory()
        ->for($openAcademicYear, 'academicYear')
        ->for($assignedDepartment)
        ->create();
    $returnedPlan = OperationalPlan::factory()
        ->returned()
        ->for($openAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $submittedPlan = OperationalPlan::factory()
        ->submitted()
        ->for($openAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $approvedPlan = OperationalPlan::factory()
        ->approved()
        ->for($openAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $closedPlan = OperationalPlan::factory()
        ->closed()
        ->for($openAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $closedAcademicYearPlan = OperationalPlan::factory()
        ->submitted()
        ->for($closedAcademicYear, 'academicYear')
        ->for($assignedDepartment)
        ->create();

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', OperationalPlan::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', OperationalPlan::class),
        'assignedUser.viewAny' => Gate::forUser($assignedUser)->allows('viewAny', OperationalPlan::class),
        'unassignedUser.viewAny' => Gate::forUser($unassignedDepartmentUser)->allows('viewAny', OperationalPlan::class),
        'administrator.viewOther' => Gate::forUser($administrator)->allows('view', $draftPlan),
        'reviewer.viewOther' => Gate::forUser($reviewer)->allows('view', $draftPlan),
        'assignedUser.viewOwn' => Gate::forUser($assignedUser)->allows('view', $draftPlan),
        'otherUser.viewOther' => Gate::forUser($otherDepartmentUser)->allows('view', $draftPlan),
        'administrator.createOpen' => Gate::forUser($administrator)->allows('create', [OperationalPlan::class, $openAcademicYear]),
        'administrator.createClosed' => Gate::forUser($administrator)->allows('create', [OperationalPlan::class, $closedAcademicYear]),
        'reviewer.createOpen' => Gate::forUser($reviewer)->allows('create', [OperationalPlan::class, $openAcademicYear]),
        'assignedUser.createOpen' => Gate::forUser($assignedUser)->allows('create', [OperationalPlan::class, $openAcademicYear]),
        'inactiveUser.createOpen' => Gate::forUser($inactiveDepartmentUser)->allows('create', [OperationalPlan::class, $openAcademicYear]),
        'assignedUser.createClosed' => Gate::forUser($assignedUser)->allows('create', [OperationalPlan::class, $closedAcademicYear]),
        'administrator.updateDraft' => Gate::forUser($administrator)->allows('update', $draftPlan),
        'administrator.updateReturned' => Gate::forUser($administrator)->allows('update', $returnedPlan),
        'administrator.updateSubmitted' => Gate::forUser($administrator)->allows('update', $submittedPlan),
        'reviewer.updateDraft' => Gate::forUser($reviewer)->allows('update', $draftPlan),
        'assignedUser.updateOwnDraft' => Gate::forUser($assignedUser)->allows('update', $draftPlan),
        'otherUser.updateOtherDraft' => Gate::forUser($otherDepartmentUser)->allows('update', $draftPlan),
        'assignedUser.updateClosedYear' => Gate::forUser($assignedUser)->allows('update', $closedAcademicYearPlan),
        'assignedUser.submitDraft' => Gate::forUser($assignedUser)->allows('submit', $draftPlan),
        'reviewer.approveSubmitted' => Gate::forUser($reviewer)->allows('approve', $submittedPlan),
        'administrator.approveSubmitted' => Gate::forUser($administrator)->allows('approve', $submittedPlan),
        'assignedUser.approveSubmitted' => Gate::forUser($assignedUser)->allows('approve', $submittedPlan),
        'reviewer.approveReturned' => Gate::forUser($reviewer)->allows('approve', $returnedPlan),
        'reviewer.returnSubmitted' => Gate::forUser($reviewer)->allows('returnForRevision', $submittedPlan),
        'reviewer.approveClosedYear' => Gate::forUser($reviewer)->allows('approve', $closedAcademicYearPlan),
        'administrator.closeApproved' => Gate::forUser($administrator)->allows('close', $approvedPlan),
        'reviewer.closeApproved' => Gate::forUser($reviewer)->allows('close', $approvedPlan),
        'administrator.closeSubmitted' => Gate::forUser($administrator)->allows('close', $submittedPlan),
        'administrator.reopenApproved' => Gate::forUser($administrator)->allows('reopen', $approvedPlan),
        'administrator.reopenClosed' => Gate::forUser($administrator)->allows('reopen', $closedPlan),
        'reviewer.reopenClosed' => Gate::forUser($reviewer)->allows('reopen', $closedPlan),
        'administrator.deleteDraft' => Gate::forUser($administrator)->allows('delete', $draftPlan),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => true,
        'assignedUser.viewAny' => true,
        'unassignedUser.viewAny' => false,
        'administrator.viewOther' => true,
        'reviewer.viewOther' => true,
        'assignedUser.viewOwn' => true,
        'otherUser.viewOther' => false,
        'administrator.createOpen' => true,
        'administrator.createClosed' => false,
        'reviewer.createOpen' => false,
        'assignedUser.createOpen' => true,
        'inactiveUser.createOpen' => false,
        'assignedUser.createClosed' => false,
        'administrator.updateDraft' => true,
        'administrator.updateReturned' => true,
        'administrator.updateSubmitted' => false,
        'reviewer.updateDraft' => false,
        'assignedUser.updateOwnDraft' => true,
        'otherUser.updateOtherDraft' => false,
        'assignedUser.updateClosedYear' => false,
        'assignedUser.submitDraft' => true,
        'reviewer.approveSubmitted' => true,
        'administrator.approveSubmitted' => true,
        'assignedUser.approveSubmitted' => false,
        'reviewer.approveReturned' => false,
        'reviewer.returnSubmitted' => true,
        'reviewer.approveClosedYear' => false,
        'administrator.closeApproved' => true,
        'reviewer.closeApproved' => false,
        'administrator.closeSubmitted' => false,
        'administrator.reopenApproved' => true,
        'administrator.reopenClosed' => true,
        'reviewer.reopenClosed' => false,
        'administrator.deleteDraft' => false,
    ]);
});
