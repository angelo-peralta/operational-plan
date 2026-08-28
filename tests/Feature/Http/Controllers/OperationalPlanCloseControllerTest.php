<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;

test('super administrators close an approved plan and record the terminal decision', function () {
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
        ->post(route('operational-plans.close', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $closedPlan = $operationalPlan->fresh();
    expect($closedPlan)
        ->status->toBe(OperationalPlanStatus::Closed)
        ->closed_by->toBe($administrator->id)
        ->updated_by->toBe($administrator->id)
        ->closed_at->not->toBeNull();
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history)
        ->actor_id->toBe($administrator->id)
        ->from_status->toBe(OperationalPlanStatus::Approved)
        ->to_status->toBe(OperationalPlanStatus::Closed);
});

test('reviewers cannot close approved plans', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.close', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Approved);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});

test('closed academic years forbid closing a plan', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.close', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertForbidden();
    expect($operationalPlan->fresh()->status)->toBe(OperationalPlanStatus::Approved);
    $this->assertDatabaseEmpty('operational_plan_status_histories');
});
