<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;

test('reviewers approve a submitted plan and record the decision snapshot', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->submitted()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create(['goal' => 'Approved goal']);
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.approve', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $approvedPlan = $operationalPlan->fresh();
    expect($approvedPlan)
        ->status->toBe(OperationalPlanStatus::Approved)
        ->approved_by->toBe($reviewer->id)
        ->updated_by->toBe($reviewer->id)
        ->approved_at->not->toBeNull();
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history)
        ->actor_id->toBe($reviewer->id)
        ->from_status->toBe(OperationalPlanStatus::Submitted)
        ->to_status->toBe(OperationalPlanStatus::Approved);
    expect($history->snapshot)->toHaveKey('goal', 'Approved goal');
});

test('department users cannot approve submitted plans', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.approve', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Submitted);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('closed academic years forbid approval', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $operationalPlan = OperationalPlan::factory()
        ->submitted()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.approve', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Submitted);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});
