<?php

use App\Enums\TargetOperator;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\User;

/** @param array<string, mixed> $overrides */
function validPhaseTwoPlanItemData(array $overrides = []): array
{
    return [
        'objective' => 'Strengthen institutional performance.',
        'strategy' => 'Implement a monitored improvement program.',
        'kpi_target_text' => 'At least 95% of milestones completed on time.',
        'target_value' => '95.5',
        'target_unit' => ' percent ',
        'target_operator' => TargetOperator::PercentageAtLeast->value,
        'target_frequency' => ' every   semester ',
        'resources_needed' => 'Approved operating budget.',
        'documentary_evidence_requirements' => ['  Accomplishment   report ', '', 'Monitoring dashboard'],
        'manual_co_accountable_units' => ['  VPA ', '', ' CEARA '],
        ...$overrides,
    ];
}

test('department users append a structured plan item with documentary and co-accountable data', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $firstCoAccountableDepartment = Department::factory()->create();
    $secondCoAccountableDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    PlanItem::factory()->for($keyResultArea)->create(['sort_order' => 1]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.plan-items.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
        ]), validPhaseTwoPlanItemData([
            'co_accountable_department_ids' => [
                $firstCoAccountableDepartment->id,
                $secondCoAccountableDepartment->id,
            ],
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $planItem = PlanItem::query()->where('sort_order', 2)->sole();
    expect($planItem)
        ->objective->toBe('Strengthen institutional performance.')
        ->target_value->toBe('95.5000')
        ->target_unit->toBe('percent')
        ->target_operator->toBe(TargetOperator::PercentageAtLeast)
        ->target_frequency->toBe('every semester')
        ->documentary_evidence_requirements->toBe(['Accomplishment report', 'Monitoring dashboard'])
        ->manual_co_accountable_units->toBe(['VPA', 'CEARA'])
        ->created_by->toBe($departmentUser->id)
        ->updated_by->toBe($departmentUser->id);
    expect($planItem->coAccountableDepartments()->pluck('departments.id')->sort()->values()->all())
        ->toBe(collect([$firstCoAccountableDepartment->id, $secondCoAccountableDepartment->id])->sort()->values()->all());
});

test('plan item creation rejects parent ordering status and audit attributes from the client', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.plan-items.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
        ]), validPhaseTwoPlanItemData([
            'key_result_area_id' => 999,
            'sort_order' => 99,
            'status' => 'approved',
            'created_by' => User::factory()->superAdmin()->create()->id,
        ]));

    $response->assertSessionHasErrors(['key_result_area_id', 'sort_order', 'status', 'created_by']);
    $this->assertDatabaseEmpty('plan_items');
});

test('plan item updates return 404 for an item from another KRA', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $administrator = User::factory()->superAdmin()->create();
    $operationalPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $otherKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create(['sort_order' => 2]);
    $otherPlanItem = PlanItem::factory()->for($otherKeyResultArea)->create([
        'objective' => 'Other KRA objective',
    ]);

    $response = $this
        ->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.plan-items.update', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
            'plan_item' => $otherPlanItem,
        ]), validPhaseTwoPlanItemData(['objective' => 'Changed objective']));

    $response->assertNotFound();
    expect($otherPlanItem->fresh()->objective)->toBe('Other KRA objective');
});

test('plan item updates replace documentary and co-accountable data atomically', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $oldCoAccountableDepartment = Department::factory()->create();
    $newCoAccountableDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Original objective',
        'documentary_evidence_requirements' => ['Old report'],
        'manual_co_accountable_units' => ['Old unit'],
    ]);
    $planItem->coAccountableDepartments()->attach($oldCoAccountableDepartment);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.plan-items.update', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
            'plan_item' => $planItem,
        ]), validPhaseTwoPlanItemData([
            'objective' => 'Updated objective',
            'documentary_evidence_requirements' => ['Updated report'],
            'manual_co_accountable_units' => ['Updated unit'],
            'co_accountable_department_ids' => [$newCoAccountableDepartment->id],
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $updatedPlanItem = $planItem->fresh();
    expect($updatedPlanItem)
        ->objective->toBe('Updated objective')
        ->documentary_evidence_requirements->toBe(['Updated report'])
        ->manual_co_accountable_units->toBe(['Updated unit'])
        ->updated_by->toBe($departmentUser->id);
    $this->assertDatabaseMissing('plan_item_co_accountables', [
        'plan_item_id' => $planItem->id,
        'department_id' => $oldCoAccountableDepartment->id,
    ]);
    $this->assertDatabaseHas('plan_item_co_accountables', [
        'plan_item_id' => $planItem->id,
        'department_id' => $newCoAccountableDepartment->id,
    ]);
});

test('department users can delete a plan item from an editable plan', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $coAccountableDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $planItem->coAccountableDepartments()->attach($coAccountableDepartment);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->delete(route('operational-plans.key-result-areas.plan-items.destroy', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
            'plan_item' => $planItem,
        ]));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertModelMissing($planItem);
    $this->assertDatabaseMissing('plan_item_co_accountables', ['plan_item_id' => $planItem->id]);
});

test('department users can reorder every plan item in an editable KRA', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $firstPlanItem = PlanItem::factory()->for($keyResultArea)->create(['sort_order' => 1]);
    $secondPlanItem = PlanItem::factory()->for($keyResultArea)->create(['sort_order' => 2]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.plan-items.reorder', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
        ]), [
            'ordered_ids' => [$secondPlanItem->id, $firstPlanItem->id],
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($firstPlanItem->fresh())
        ->sort_order->toBe(2)
        ->updated_by->toBe($departmentUser->id)
        ->and($secondPlanItem->fresh())
        ->sort_order->toBe(1)
        ->updated_by->toBe($departmentUser->id);
});

test('plan item reorder rejects an incomplete ordering without changing positions', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $firstPlanItem = PlanItem::factory()->for($keyResultArea)->create(['sort_order' => 1]);
    $secondPlanItem = PlanItem::factory()->for($keyResultArea)->create(['sort_order' => 2]);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('operational-plans.key-result-areas.plan-items.reorder', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
            'key_result_area' => $keyResultArea,
        ]), [
            'ordered_ids' => [$secondPlanItem->id],
        ]);

    $response->assertSessionHasErrors([
        'ordered_ids' => 'The Plan Item order must contain every item in this KRA exactly once.',
    ]);
    expect($firstPlanItem->fresh()->sort_order)->toBe(1)
        ->and($secondPlanItem->fresh()->sort_order)->toBe(2);
});

test('reviewers and submitted plans forbid source plan item edits', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $reviewer = User::factory()->reviewer()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $submittedPlan = OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($submittedPlan)->create();

    $reviewerResponse = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.plan-items.store', [
            'current_team' => $reviewer->currentTeam,
            'operational_plan' => $submittedPlan,
            'key_result_area' => $keyResultArea,
        ]), validPhaseTwoPlanItemData());
    $departmentResponse = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.key-result-areas.plan-items.store', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $submittedPlan,
            'key_result_area' => $keyResultArea,
        ]), validPhaseTwoPlanItemData());

    $reviewerResponse->assertForbidden();
    $departmentResponse->assertForbidden();
    $this->assertDatabaseEmpty('plan_items');
});
