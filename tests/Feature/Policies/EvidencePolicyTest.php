<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;

test('evidence permissions enforce department ownership and reviewer read-only access', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $otherDepartmentUser = User::factory()->departmentUser()->create();
    $reviewer = User::factory()->reviewer()->create();
    $administrator = User::factory()->superAdmin()->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();
    $accomplishment->load('planItem.keyResultArea.operationalPlan.academicYear');
    $evidence = Evidence::factory()->for($accomplishment)->for($departmentUser, 'uploader')->create();
    $evidence->setRelation('accomplishment', $accomplishment);

    expect($departmentUser->can('create', [Evidence::class, $accomplishment]))->toBeTrue()
        ->and($reviewer->can('create', [Evidence::class, $accomplishment]))->toBeFalse()
        ->and($administrator->can('create', [Evidence::class, $accomplishment]))->toBeFalse()
        ->and($departmentUser->can('view', $evidence))->toBeTrue()
        ->and($otherDepartmentUser->can('view', $evidence))->toBeFalse()
        ->and($reviewer->can('view', $evidence))->toBeTrue()
        ->and($administrator->can('view', $evidence))->toBeTrue();
});

test('returned accomplishments accept additional evidence but finalized accomplishments do not', function (AccomplishmentStatus $status, bool $allowed) {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create(['status' => $status]);

    expect($departmentUser->can('create', [Evidence::class, $accomplishment]))->toBe($allowed);
})->with([
    'draft' => [AccomplishmentStatus::Draft, true],
    'returned' => [AccomplishmentStatus::Returned, true],
    'submitted' => [AccomplishmentStatus::Submitted, false],
    'accepted' => [AccomplishmentStatus::Accepted, false],
    'rejected' => [AccomplishmentStatus::Rejected, false],
]);

test('draft and closed reporting periods reject evidence uploads', function (ReportingPeriodStatus $periodStatus) {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->for($academicYear)->create(['status' => $periodStatus]);
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    expect($departmentUser->can('create', [Evidence::class, $accomplishment]))->toBeFalse();
})->with([
    'draft reporting period' => ReportingPeriodStatus::Draft,
    'closed reporting period' => ReportingPeriodStatus::Closed,
]);

test('evidence uploads reject a reporting period from another academic year', function () {
    $planAcademicYear = AcademicYear::factory()->open()->create();
    $periodAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->for($periodAcademicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($planAcademicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    expect($departmentUser->can('create', [Evidence::class, $accomplishment]))->toBeFalse();
});
