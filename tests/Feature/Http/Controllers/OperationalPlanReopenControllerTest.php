<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;

test('super administrators reopen a closed plan for revision with a required reason', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->closed()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create(['goal' => 'Previously closed goal']);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.reopen', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'remarks' => '  Reopen after an approved institutional amendment.  ',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $reopenedPlan = $operationalPlan->fresh();
    expect($reopenedPlan)
        ->status->toBe(OperationalPlanStatus::Returned)
        ->returned_by->toBe($administrator->id)
        ->updated_by->toBe($administrator->id)
        ->returned_at->not->toBeNull();
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history)
        ->actor_id->toBe($administrator->id)
        ->from_status->toBe(OperationalPlanStatus::Closed)
        ->to_status->toBe(OperationalPlanStatus::Returned)
        ->remarks->toBe('Reopen after an approved institutional amendment.');
    expect($history->snapshot)->toHaveKey('goal', 'Previously closed goal');
});

test('super administrators may reopen an approved plan before closure', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.reopen', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'Correct the approved source plan.']);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Returned);
    $this->assertDatabaseHas('operational_plan_status_histories', [
        'operational_plan_id' => $operationalPlan->id,
        'actor_id' => $administrator->id,
        'from_status' => OperationalPlanStatus::Approved->value,
        'to_status' => OperationalPlanStatus::Returned->value,
        'remarks' => 'Correct the approved source plan.',
    ]);
});

test('reopening a plan requires a substantive reason', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->closed()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.reopen', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => '   ']);

    $response->assertSessionHasErrors([
        'remarks' => 'The remarks field is required.',
    ]);
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Closed);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('reviewers cannot reopen closed plans', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->closed()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.reopen', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'Unauthorized reopening.']);

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Closed);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('closed academic years forbid reopening a plan', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $operationalPlan = OperationalPlan::factory()
        ->closed()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.reopen', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), ['remarks' => 'Attempt closed-year reopening.']);

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Closed);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});
