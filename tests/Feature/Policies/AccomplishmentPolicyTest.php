<?php

use App\Enums\AccomplishmentStatus;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

function accomplishmentPolicyContext(): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $reportingPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($reportingPeriod)->create();

    return compact(
        'academicYear',
        'department',
        'departmentUser',
        'reportingPeriod',
        'operationalPlan',
        'keyResultArea',
        'planItem',
        'accomplishment',
    );
}

test('view permissions expose accomplishments only to institution reviewers and the owning department', function () {
    extract(accomplishmentPolicyContext());
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $otherDepartmentUser = User::factory()->departmentUser()->create();
    $unassignedDepartmentUser = User::factory()->create(['department_id' => null]);

    expect([
        'administrator.viewAny' => Gate::forUser($administrator)->allows('viewAny', Accomplishment::class),
        'reviewer.viewAny' => Gate::forUser($reviewer)->allows('viewAny', Accomplishment::class),
        'departmentUser.viewAny' => Gate::forUser($departmentUser)->allows('viewAny', Accomplishment::class),
        'unassignedUser.viewAny' => Gate::forUser($unassignedDepartmentUser)->allows('viewAny', Accomplishment::class),
        'administrator.view' => Gate::forUser($administrator)->allows('view', $accomplishment),
        'reviewer.view' => Gate::forUser($reviewer)->allows('view', $accomplishment),
        'departmentUser.viewOwn' => Gate::forUser($departmentUser)->allows('view', $accomplishment),
        'otherDepartmentUser.viewOther' => Gate::forUser($otherDepartmentUser)->allows('view', $accomplishment),
    ])->toBe([
        'administrator.viewAny' => true,
        'reviewer.viewAny' => true,
        'departmentUser.viewAny' => true,
        'unassignedUser.viewAny' => false,
        'administrator.view' => true,
        'reviewer.view' => true,
        'departmentUser.viewOwn' => true,
        'otherDepartmentUser.viewOther' => false,
    ]);
});

test('create permissions require the owning department approved plan open year and matching open period', function () {
    extract(accomplishmentPolicyContext());
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $otherDepartmentUser = User::factory()->departmentUser()->create();
    $unassignedDepartmentUser = User::factory()->create(['department_id' => null]);

    $draftPlanDepartment = Department::factory()->create();
    $draftPlanDepartmentUser = User::factory()
        ->departmentUser()
        ->forDepartment($draftPlanDepartment)
        ->create();
    $draftPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for($draftPlanDepartment)
        ->create();
    $draftPlanItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($draftPlan))
        ->create();
    $closedPeriod = ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYearPeriod = ReportingPeriod::factory()->open()->for($otherAcademicYear)->create();
    $closedAcademicYear = AcademicYear::factory()->closed()->create();
    $closedAcademicYearPeriod = ReportingPeriod::factory()->open()->for($closedAcademicYear)->create();
    $closedAcademicYearPlan = OperationalPlan::factory()
        ->approved()
        ->for($closedAcademicYear, 'academicYear')
        ->for($department)
        ->create();
    $closedAcademicYearPlanItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($closedAcademicYearPlan))
        ->create();

    expect([
        'departmentUser.createOwn' => Gate::forUser($departmentUser)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
        'administrator.create' => Gate::forUser($administrator)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
        'reviewer.create' => Gate::forUser($reviewer)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
        'otherDepartmentUser.createOther' => Gate::forUser($otherDepartmentUser)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
        'unassignedDepartmentUser.create' => Gate::forUser($unassignedDepartmentUser)->allows('create', [Accomplishment::class, $planItem, $reportingPeriod]),
        'departmentUser.createForDraftPlan' => Gate::forUser($draftPlanDepartmentUser)->allows('create', [Accomplishment::class, $draftPlanItem, $reportingPeriod]),
        'departmentUser.createForClosedPeriod' => Gate::forUser($departmentUser)->allows('create', [Accomplishment::class, $planItem, $closedPeriod]),
        'departmentUser.createAcrossAcademicYears' => Gate::forUser($departmentUser)->allows('create', [Accomplishment::class, $planItem, $otherAcademicYearPeriod]),
        'departmentUser.createInClosedAcademicYear' => Gate::forUser($departmentUser)->allows('create', [Accomplishment::class, $closedAcademicYearPlanItem, $closedAcademicYearPeriod]),
    ])->toBe([
        'departmentUser.createOwn' => true,
        'administrator.create' => false,
        'reviewer.create' => false,
        'otherDepartmentUser.createOther' => false,
        'unassignedDepartmentUser.create' => false,
        'departmentUser.createForDraftPlan' => false,
        'departmentUser.createForClosedPeriod' => false,
        'departmentUser.createAcrossAcademicYears' => false,
        'departmentUser.createInClosedAcademicYear' => false,
    ]);
});

test('update permissions allow only owning department drafts and never allow deletion', function () {
    extract(accomplishmentPolicyContext());
    $administrator = User::factory()->superAdmin()->create();
    $reviewer = User::factory()->reviewer()->create();
    $otherDepartmentUser = User::factory()->departmentUser()->create();

    $updatePermissions = [];

    foreach (AccomplishmentStatus::cases() as $status) {
        $statusPlanItem = PlanItem::factory()->for($keyResultArea)->create();
        $statusAccomplishment = Accomplishment::factory()
            ->for($statusPlanItem)
            ->for($reportingPeriod)
            ->create(['status' => $status]);
        $updatePermissions[$status->value] = Gate::forUser($departmentUser)->allows('update', $statusAccomplishment);
    }

    expect($updatePermissions)->toBe([
        AccomplishmentStatus::Draft->value => true,
        AccomplishmentStatus::Submitted->value => false,
        AccomplishmentStatus::QueuedForAnalysis->value => false,
        AccomplishmentStatus::Analyzing->value => false,
        AccomplishmentStatus::ReadyForReview->value => false,
        AccomplishmentStatus::Accepted->value => false,
        AccomplishmentStatus::Returned->value => false,
        AccomplishmentStatus::Rejected->value => false,
    ]);
    expect([
        'administrator.update' => Gate::forUser($administrator)->allows('update', $accomplishment),
        'reviewer.update' => Gate::forUser($reviewer)->allows('update', $accomplishment),
        'otherDepartmentUser.update' => Gate::forUser($otherDepartmentUser)->allows('update', $accomplishment),
        'departmentUser.delete' => Gate::forUser($departmentUser)->allows('delete', $accomplishment),
        'administrator.restore' => Gate::forUser($administrator)->allows('restore', $accomplishment),
        'administrator.forceDelete' => Gate::forUser($administrator)->allows('forceDelete', $accomplishment),
    ])->toBe([
        'administrator.update' => false,
        'reviewer.update' => false,
        'otherDepartmentUser.update' => false,
        'departmentUser.delete' => false,
        'administrator.restore' => false,
        'administrator.forceDelete' => false,
    ]);
});
