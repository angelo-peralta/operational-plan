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

test('submission requires at least one key result area', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertSessionHasErrors([
        'plan' => 'Add at least one Key Result Area before submitting the plan.',
    ]);
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Draft);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('submission requires every key result area to contain a plan item', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $completeKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 1]);
    PlanItem::factory()->for($completeKeyResultArea)->create();
    KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 2]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertSessionHasErrors([
        'plan' => 'Every Key Result Area must contain at least one Plan Item before submission.',
    ]);
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Draft);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('department users submit a complete draft with an immutable aggregate snapshot', function () {
    $academicYear = AcademicYear::factory()->open()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $department = Department::factory()->create(['name' => 'Planning Office', 'code' => 'PLAN']);
    $coAccountableDepartment = Department::factory()->create(['name' => 'Quality Office', 'code' => 'QA']);
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create([
        'accountable_name' => 'Planning Director',
        'goal' => 'Deliver all institutional milestones.',
    ]);
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create([
        'code' => 'KRA 1',
        'name' => 'Institutional Performance',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Complete annual priorities.',
        'kpi_target_text' => '100% of milestones completed.',
        'documentary_evidence_requirements' => ['Accomplishment report'],
        'manual_co_accountable_units' => ['VPA'],
    ]);
    $planItem->coAccountableDepartments()->attach($coAccountableDepartment);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $submittedPlan = $operationalPlan->fresh();
    expect($submittedPlan)
        ->status->toBe(OperationalPlanStatus::Submitted)
        ->submitted_by->toBe($departmentUser->id)
        ->updated_by->toBe($departmentUser->id)
        ->submitted_at->not->toBeNull();
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history)
        ->actor_id->toBe($departmentUser->id)
        ->from_status->toBe(OperationalPlanStatus::Draft)
        ->to_status->toBe(OperationalPlanStatus::Submitted)
        ->remarks->toBeNull();
    expect($history->snapshot)
        ->toHaveKey('academic_year.name', 'AY 2025-2026')
        ->toHaveKey('department.code', 'PLAN')
        ->toHaveKey('goal', 'Deliver all institutional milestones.')
        ->toHaveKey('key_result_areas.0.plan_items.0.kpi_target_text', '100% of milestones completed.')
        ->toHaveKey('key_result_areas.0.plan_items.0.documentary_evidence_requirements', ['Accomplishment report'])
        ->toHaveKey('key_result_areas.0.plan_items.0.manual_co_accountable_units', ['VPA'])
        ->toHaveKey('key_result_areas.0.plan_items.0.co_accountable_departments.0.code', 'QA');
});

test('department users receive 404 when submitting another department plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $departmentUser = User::factory()->departmentUser()->create();
    $otherPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $otherPlan,
        ]));

    $response->assertNotFound();
    expect($otherPlan->fresh()->status)->toBe(OperationalPlanStatus::Draft);
});

test('closed academic years forbid submission', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    PlanItem::factory()->for($keyResultArea)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.submit', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Draft);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});
