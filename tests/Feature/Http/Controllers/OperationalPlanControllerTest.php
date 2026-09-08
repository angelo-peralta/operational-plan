<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;
use Illuminate\Support\Collection;
use Inertia\Testing\AssertableInertia as Assert;

test('department users see only their plan in the selected academic year', function () {
    $this->withoutVite();

    $selectedAcademicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $ownPlan = OperationalPlan::factory()
        ->for($selectedAcademicYear, 'academicYear')
        ->for($department)
        ->create(['goal' => 'Selected year goal']);
    OperationalPlan::factory()
        ->for($selectedAcademicYear, 'academicYear')
        ->for($otherDepartment)
        ->create(['goal' => 'Other department goal']);
    OperationalPlan::factory()
        ->for($otherAcademicYear, 'academicYear')
        ->for($department)
        ->create(['goal' => 'Other year goal']);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('operational-plans.index', ['current_team' => $departmentUser->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('operational-plans/index')
        ->has('operationalPlans', 1)
        ->where('operationalPlans.0.id', $ownPlan->id)
        ->where('operationalPlans.0.goal', 'Selected year goal')
        ->has('targetOrganizationalUnits', 0));
});

test('root executives see subordinate plans only in the selected academic year', function () {
    $this->withoutVite();

    $selectedAcademicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $firstPlan = OperationalPlan::factory()
        ->for($selectedAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $secondPlan = OperationalPlan::factory()
        ->for($selectedAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $availableOrganizationalUnit = Department::factory()->create();
    OperationalPlan::factory()
        ->for($otherAcademicYear, 'academicYear')
        ->for($availableOrganizationalUnit)
        ->create();
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();
    $executiveAuthorityId = $reviewer->organizationalUnitAssignments()->sole()->organizational_unit_id;

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('operational-plans.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('operational-plans/index')
        ->has('operationalPlans', 2)
        ->where('operationalPlans', fn (Collection $plans): bool => $plans->pluck('id')->sort()->values()->all() === collect([$firstPlan->id, $secondPlan->id])->sort()->values()->all())
        ->has('targetOrganizationalUnits', 1)
        ->where('targetOrganizationalUnits.0.id', $executiveAuthorityId)
        ->where('accountableUsers.0.organizationalUnitId', $executiveAuthorityId)
        ->missing('accountableUsers.0.departmentId'));
});

test('department users receive 404 for another department plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $departmentUser = User::factory()->departmentUser()->create();
    $otherPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('operational-plans.show', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $otherPlan,
        ]));

    $response->assertNotFound();
});

test('requests receive 404 when a plan is outside the selected academic year', function () {
    $selectedAcademicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $administrator = User::factory()->superAdmin()->create();
    $otherYearPlan = OperationalPlan::factory()
        ->for($otherAcademicYear, 'academicYear')
        ->for(Department::factory())
        ->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('operational-plans.show', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $otherYearPlan,
        ]));

    $response->assertNotFound();
});

test('department users create a draft plan in their department and selected academic year', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create([
        'name' => 'Department Planner',
    ]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $departmentUser->currentTeam]), [
            'organizational_unit_id' => $department->id,
            'accountable_user_id' => $departmentUser->id,
            'accountable_name' => 'Department Planner',
            'accountable_position' => 'Planning Coordinator',
            'goal' => 'Improve operational effectiveness.',
        ]);

    $operationalPlan = OperationalPlan::query()->sole();
    $response
        ->assertSessionHasNoErrors()
        ->assertRedirectToRoute('operational-plans.show', [
            'current_team' => (string) $departmentUser->currentTeam?->getRouteKey(),
            'operational_plan' => $operationalPlan,
        ]);
    expect($operationalPlan)
        ->academic_year_id->toBe($academicYear->id)
        ->organizational_unit_id->toBe($department->id)
        ->status->toBe(OperationalPlanStatus::Draft)
        ->created_by->toBe($departmentUser->id)
        ->updated_by->toBe($departmentUser->id);
    $this->assertDatabaseHas('operational_plan_status_histories', [
        'operational_plan_id' => $operationalPlan->id,
        'actor_id' => $departmentUser->id,
        'from_status' => null,
        'to_status' => OperationalPlanStatus::Draft->value,
    ]);
    $history = OperationalPlanStatusHistory::query()->sole();
    expect($history->snapshot)
        ->toHaveKey('academic_year.id', $academicYear->id)
        ->toHaveKey('organizational_unit.id', $department->id)
        ->toHaveKey('goal', 'Improve operational effectiveness.');
});

test('super administrators create a plan for the selected active department', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $accountableUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $administrator->currentTeam]), [
            'organizational_unit_id' => $department->id,
            'accountable_user_id' => $accountableUser->id,
            'accountable_name' => $accountableUser->name,
            'accountable_position' => 'Director',
            'goal' => 'Deliver the annual operational plan.',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('operational_plans', [
        'academic_year_id' => $academicYear->id,
        'organizational_unit_id' => $department->id,
        'accountable_user_id' => $accountableUser->id,
        'status' => OperationalPlanStatus::Draft->value,
        'created_by' => $administrator->id,
    ]);
});

test('plan creation rejects client controlled scope workflow and audit fields', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $departmentUser->currentTeam]), [
            'academic_year_id' => $otherAcademicYear->id,
            'organizational_unit_id' => $otherDepartment->id,
            'status' => OperationalPlanStatus::Approved->value,
            'created_by' => User::factory()->superAdmin()->create()->id,
            'submitted_at' => now()->toISOString(),
            'accountable_name' => 'Planner',
            'goal' => 'Attempt unauthorized ownership changes.',
        ]);

    $response->assertSessionHasErrors([
        'academic_year_id',
        'organizational_unit_id',
        'status',
        'created_by',
        'submitted_at',
    ]);
    $this->assertDatabaseEmpty('operational_plans');
});

test('plan creation rejects duplicate department and academic year ownership', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $departmentUser->currentTeam]), [
            'organizational_unit_id' => $department->id,
            'accountable_name' => 'Planner',
            'goal' => 'Duplicate plan.',
        ]);

    $response->assertSessionHasErrors([
        'organizational_unit_id' => 'This organizational unit already has an Operational Plan for the selected Academic Year.',
    ]);
    $this->assertDatabaseCount('operational_plans', 1);
});

test('closed academic years forbid plan creation and editing', function () {
    $academicYear = AcademicYear::factory()->closed()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create([
        'goal' => 'Original goal',
    ]);

    $createResponse = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $departmentUser->currentTeam]), [
            'accountable_name' => 'Planner',
            'goal' => 'New goal',
        ]);
    $updateResponse = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.update', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'accountable_name' => 'Planner',
            'goal' => 'Changed goal',
        ]);

    $createResponse->assertForbidden();
    $updateResponse->assertForbidden();
    expect($operationalPlan->fresh()->goal)->toBe('Original goal');
});

test('plan updates require an accountable user from the owning department', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $otherUser = User::factory()->departmentUser()->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create([
        'goal' => 'Original goal',
    ]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.update', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'accountable_user_id' => $otherUser->id,
            'accountable_name' => 'Other User',
            'accountable_position' => 'Director',
            'goal' => 'Changed goal',
        ]);

    $response->assertSessionHasErrors([
        'accountable_user_id' => 'The accountable user must belong to the plan organizational unit.',
    ]);
    expect($operationalPlan->fresh()->goal)->toBe('Original goal');
});
