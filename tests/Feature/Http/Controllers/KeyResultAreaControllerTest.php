<?php

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;

test('department users append a normalized key result area to an editable plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 1]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'code' => '  KRA   2 ',
            'name' => '  Community   Engagement ',
            'description' => '  Improve external partnerships.  ',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('key_result_areas', [
        'operational_plan_id' => $operationalPlan->id,
        'code' => 'KRA 2',
        'name' => 'Community Engagement',
        'description' => 'Improve external partnerships.',
        'sort_order' => 2,
        'created_by' => $departmentUser->id,
        'updated_by' => $departmentUser->id,
    ]);
});

test('key result area creation rejects parent ordering and audit attributes from the client', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'name' => 'Unauthorized KRA',
            'operational_plan_id' => 999,
            'sort_order' => 99,
            'created_by' => User::factory()->superAdmin()->create()->id,
        ]);

    $response->assertSessionHasErrors(['operational_plan_id', 'sort_order', 'created_by']);
    $this->assertDatabaseEmpty('key_result_areas');
});

test('key result area updates return 404 for a KRA from another plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $administrator = User::factory()->superAdmin()->create();
    $operationalPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $otherPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $otherKeyResultArea = KeyResultArea::factory()->for($otherPlan)->create([
        'name' => 'Other plan KRA',
    ]);

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.update', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $otherKeyResultArea,
        ]), [
            'name' => 'Changed KRA',
        ]);

    $response->assertNotFound();
    expect($otherKeyResultArea->fresh()->name)->toBe('Other plan KRA');
});

test('department users can reorder every KRA in their editable plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $firstKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 1]);
    $secondKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 2]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.reorder', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'ordered_ids' => [$secondKeyResultArea->id, $firstKeyResultArea->id],
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($firstKeyResultArea->fresh())
        ->sort_order->toBe(2)
        ->updated_by->toBe($departmentUser->id)
        ->and($secondKeyResultArea->fresh())
        ->sort_order->toBe(1)
        ->updated_by->toBe($departmentUser->id);
});

test('KRA reorder rejects an incomplete ordering without changing positions', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $firstKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 1]);
    $secondKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 2]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.reorder', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'ordered_ids' => [$secondKeyResultArea->id],
        ]);

    $response->assertSessionHasErrors([
        'ordered_ids' => 'The KRA order must contain every KRA in this Operational Plan exactly once.',
    ]);
    expect($firstKeyResultArea->fresh()->sort_order)->toBe(1)
        ->and($secondKeyResultArea->fresh()->sort_order)->toBe(2);
});

test('reviewers cannot add KRAs to source plans', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $operationalPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.store', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $operationalPlan,
        ]), [
            'name' => 'Reviewer KRA',
        ]);

    $response->assertForbidden();
    $this->assertDatabaseEmpty('key_result_areas');
});

test('submitted and closed-year plans forbid KRA edits', function () {
    $openAcademicYear = AcademicYear::factory()->open()->create();
    $closedAcademicYear = AcademicYear::factory()->closed()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $submittedPlan = OperationalPlan::factory()->submitted()->for($openAcademicYear, 'academicYear')->for($department)->create();
    $closedYearPlan = OperationalPlan::factory()->for($closedAcademicYear, 'academicYear')->for($department)->create();

    $submittedResponse = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $openAcademicYear->id])
        ->post(route('operational-plans.key-result-areas.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $submittedPlan,
        ]), ['name' => 'Locked KRA']);
    $closedYearResponse = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $closedAcademicYear->id])
        ->post(route('operational-plans.key-result-areas.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $closedYearPlan,
        ]), ['name' => 'Closed-year KRA']);

    $submittedResponse->assertForbidden();
    $closedYearResponse->assertForbidden();
    $this->assertDatabaseEmpty('key_result_areas');
});
