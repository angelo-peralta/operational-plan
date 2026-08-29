<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('department users see only their approved plan in the selected academic year and reporting period', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $user = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $otherPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($otherAcademicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create(['kpi_target_text' => 'Train 20 staff']);
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create([
        'accomplishment_text' => 'Trained 10 staff',
    ]);
    OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for(Department::factory())->create();
    OperationalPlan::factory()->approved()->for($otherAcademicYear, 'academicYear')->for($department)->create();

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period->id,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('monitoring/index')
        ->where('academicYear.id', $academicYear->id)
        ->where('selectedReportingPeriod.id', $period->id)
        ->has('reportingPeriods', 1)
        ->has('operationalPlans', 1)
        ->where('operationalPlans.0.id', $plan->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.id', $planItem->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.id', $accomplishment->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.updateAccomplishment', true));

    expect($otherPeriod->academic_year_id)->toBe($otherAcademicYear->id);
});

test('reviewers can monitor all departments but cannot author accomplishment drafts', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $reviewer = User::factory()->reviewer()->create();

    foreach (range(1, 2) as $sortOrder) {
        $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for(Department::factory())->create();
        $keyResultArea = KeyResultArea::factory()->for($plan)->create(['sort_order' => $sortOrder]);
        PlanItem::factory()->for($keyResultArea)->create();
    }

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('monitoring/index')
        ->has('operationalPlans', 2)
        ->where('selectedReportingPeriod.id', $period->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false)
        ->where('operationalPlans.1.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false));
});

test('closed academic year monitoring remains visible but read only', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->closed()->create();
    $department = Department::factory()->create();
    $user = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->closed()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('academicYear.id', $academicYear->id)
        ->where('operationalPlans.0.id', $plan->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.id', $accomplishment->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.updateAccomplishment', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.uploadEvidence', false));
});

test('monitoring rejects reporting periods from another academic year with 404', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->for($otherAcademicYear)->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $reviewer->currentTeam,
            'reporting_period' => $otherPeriod->id,
        ]));

    $response->assertNotFound();
});

test('monitoring excludes draft and submitted plans', function (OperationalPlanStatus $status) {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    ReportingPeriod::factory()->open()->for($academicYear)->create();
    $reviewer = User::factory()->reviewer()->create();
    OperationalPlan::factory()->for($academicYear, 'academicYear')->for(Department::factory())->create([
        'status' => $status,
    ]);

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->has('operationalPlans', 0));
})->with([
    'draft' => OperationalPlanStatus::Draft,
    'submitted' => OperationalPlanStatus::Submitted,
]);
