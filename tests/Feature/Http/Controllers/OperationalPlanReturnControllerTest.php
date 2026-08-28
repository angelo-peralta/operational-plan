<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\PlanItem;
use App\Models\User;

test('reviewers return a submitted plan with required remarks and a decision snapshot', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->submitted()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create(['goal' => 'Goal before return']);
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.return', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'remarks' => '  Clarify the evidence requirements.  ',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $returnedPlan = $operationalPlan->fresh();
    expect($returnedPlan)
        ->status->toBe(OperationalPlanStatus::Returned)
        ->returned_by->toBe($reviewer->id)
        ->updated_by->toBe($reviewer->id)
        ->returned_at->not->toBeNull();
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history)
        ->actor_id->toBe($reviewer->id)
        ->from_status->toBe(OperationalPlanStatus::Submitted)
        ->to_status->toBe(OperationalPlanStatus::Returned)
        ->remarks->toBe('Clarify the evidence requirements.');
    expect($history->snapshot)->toHaveKey('goal', 'Goal before return');
});

test('returning a plan requires a substantive reason', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->submitted()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.return', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => '   ']);

    $response->assertSessionHasErrors([
        'remarks' => 'The remarks field is required.',
    ]);
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Submitted);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('return and resubmission cycles preserve every decision and historical snapshot', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $reviewer = User::factory()->reviewer()->create();
    $operationalPlan = OperationalPlan::factory()
        ->submitted()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Complete the target.',
        'kpi_target_text' => 'Version one target',
    ]);

    $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.return', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'First revision reason.'])
        ->assertSessionHasNoErrors();

    $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.plan-items.update', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
            'plan_item' => $planItem,
        ]), [
            'objective' => 'Complete the target.',
            'kpi_target_text' => 'Version two target',
        ])
        ->assertSessionHasNoErrors();
    $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]))
        ->assertSessionHasNoErrors();

    $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.return', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'Second revision reason.'])
        ->assertSessionHasNoErrors();
    $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]))
        ->assertSessionHasNoErrors();

    $histories = OperationalPlanStatusHistory::query()->oldest('id')->get();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Submitted);
    expect($histories->pluck('to_status')->all())->toBe([
        OperationalPlanStatus::Returned,
        OperationalPlanStatus::Submitted,
        OperationalPlanStatus::Returned,
        OperationalPlanStatus::Submitted,
    ]);
    expect($histories->pluck('remarks')->all())->toBe([
        'First revision reason.',
        null,
        'Second revision reason.',
        null,
    ]);
    expect($histories[2]->snapshot)->toHaveKey('key_result_areas.0.plan_items.0.kpi_target_text', 'Version two target');
    expect($histories[1]->snapshot)->toHaveKey('key_result_areas.0.plan_items.0.kpi_target_text', 'Version two target');
    expect($histories[0]->snapshot)->toHaveKey('key_result_areas.0.plan_items.0.kpi_target_text', 'Version one target');
});

test('department users cannot return submitted plans for revision', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.return', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'Unauthorized return.']);

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Submitted);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});
