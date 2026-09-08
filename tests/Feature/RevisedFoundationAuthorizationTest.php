<?php

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AssignmentType;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('assigned executives have university-wide visibility while review authority remains hierarchical', function () {
    $root = OrganizationalUnit::factory()->university()->create();
    $academic = OrganizationalUnit::factory()->for($root, 'parent')->create();
    $school = OrganizationalUnit::factory()->for($academic, 'parent')->create();
    $finance = OrganizationalUnit::factory()->for($root, 'parent')->create();
    $president = User::factory()->executive()->assignedToUnit($root)->create();
    $vp = User::factory()->executive()->assignedToUnit($academic)->create();
    $head = User::factory()->officeHead()->assignedToUnit($school)->create();
    $staff = User::factory()->staff()->assignedToUnit($school)->create();
    $scope = app(ResolveOrganizationalScope::class);

    expect($scope->visibleUnitIds($president))->toEqualCanonicalizing([$root->id, $academic->id, $school->id, $finance->id])
        ->and($scope->visibleUnitIds($vp))->toEqualCanonicalizing([$root->id, $academic->id, $school->id, $finance->id])
        ->and($scope->oversightUnitIds($president))->toEqualCanonicalizing([$academic->id, $school->id, $finance->id])
        ->and($scope->oversightUnitIds($vp))->toEqualCanonicalizing([$academic->id, $school->id, $finance->id])
        ->and($scope->visibleUnitIds($head))->toBe([$school->id])
        ->and($scope->visibleUnitIds($staff))->toBe([$school->id])
        ->and($scope->canReviewUnit($vp, $school->id))->toBeTrue()
        ->and($scope->canReviewUnit($vp, $academic->id))->toBeFalse()
        ->and($scope->canReviewUnit($vp, $finance->id))->toBeFalse()
        ->and($scope->canReviewUnit($president, $academic->id))->toBeTrue()
        ->and($scope->canSubmitUnit($staff, $school->id))->toBeFalse()
        ->and($scope->canSubmitUnit($head, $school->id))->toBeTrue();
});

test('an executive without a current compatible assignment has no operational visibility', function () {
    OrganizationalUnit::factory()->university()->create();
    $executive = User::factory()->executive()->create();

    expect(app(ResolveOrganizationalScope::class)->visibleUnitIds($executive))->toBe([]);
});

test('expired future inactive and incompatible assignments grant no record access', function (array $attributes) {
    $this->travelTo(now()->setDate(2026, 9, 6));
    $unit = OrganizationalUnit::factory()->create();
    $user = User::factory()->staff()->assignedToUnit($unit)->create();
    $user->organizationalUnitAssignments()->update($attributes);

    expect(app(ResolveOrganizationalScope::class)->visibleUnitIds($user))->toBe([]);
})->with([
    'expired' => [['ends_on' => '2026-09-05']],
    'future' => [['starts_on' => '2026-09-07']],
    'inactive' => [['is_active' => false]],
    'wrong type' => [['assignment_type' => AssignmentType::Executive->value]],
]);

test('multiple assignments are inclusive of start and end dates and legacy department grants nothing', function () {
    $this->travelTo(now()->setDate(2026, 9, 6));
    $unit = OrganizationalUnit::factory()->create();
    $other = OrganizationalUnit::factory()->create();
    $legacy = Department::factory()->create();
    $user = User::factory()->staff()->assignedToUnit($unit)->create();
    $user->organizationalUnitAssignments()->create([
        'organizational_unit_id' => $other->id,
        'assignment_type' => AssignmentType::Staff,
        'starts_on' => '2026-09-06', 'ends_on' => '2026-09-06',
    ]);
    $user->update(['department_id' => $legacy->id]);

    expect(app(ResolveOrganizationalScope::class)->visibleUnitIds($user))->toEqualCanonicalizing([$unit->id, $other->id]);
});

test('inactive units retain history visibility but deny authoring', function () {
    $unit = OrganizationalUnit::factory()->inactive()->create();
    $user = User::factory()->officeHead()->assignedToUnit($unit)->create();
    $scope = app(ResolveOrganizationalScope::class);

    expect($scope->canViewUnit($user, $unit->id))->toBeTrue()
        ->and($scope->canManageUnit($user, $unit->id))->toBeFalse()
        ->and($scope->canSubmitUnit($user, $unit->id))->toBeFalse();
});

test('system administration grants neither plan approval nor evaluation and staff cannot submit', function () {
    $plan = OperationalPlan::factory()->submitted()->create();
    $admin = User::factory()->systemAdmin()->create();
    $staff = User::factory()->staff()->assignedToUnit($plan->department)->create();
    $accomplishment = Accomplishment::factory()->readyForReview()->create();

    expect(Gate::forUser($admin)->allows('approve', $plan))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('create', [Evaluation::class, $accomplishment]))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('submit', $plan))->toBeFalse()
        ->and(Gate::forUser($staff)->allows('submit', $accomplishment))->toBeFalse();
});

test('vp private record lists and dashboard include all branches but still isolate academic years', function () {
    $academic = OrganizationalUnit::factory()->create();
    $school = OrganizationalUnit::factory()->for($academic, 'parent')->create();
    $vp = User::factory()->executive()->assignedToUnit($academic)->create();
    $year = AcademicYear::factory()->open()->create();
    $ownPlan = OperationalPlan::factory()->for($year)->create(['department_id' => $school->id]);
    $otherPlan = OperationalPlan::factory()->for($year)->create();
    OperationalPlan::factory()->create(['department_id' => $school->id]);

    $this->actingAs($vp)->withSession(['selected_academic_year_id' => $year->id])
        ->get(route('operational-plans.index', ['current_team' => $vp->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page->has('operationalPlans', 2));

    $this->get(route('operational-plans.show', ['current_team' => $vp->currentTeam, 'operational_plan' => $ownPlan]))->assertOk();
    $this->get(route('operational-plans.show', ['current_team' => $vp->currentTeam, 'operational_plan' => $otherPlan]))->assertOk();
    $this->get(route('dashboard', ['current_team' => $vp->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page->where('summary.plans.total', 2));
});

test('staff can edit assigned drafts but cannot perform final plan or accomplishment submission', function () {
    $year = AcademicYear::factory()->open()->create();
    $unit = OrganizationalUnit::factory()->create();
    $staff = User::factory()->staff()->assignedToUnit($unit)->create();
    $plan = OperationalPlan::factory()->for($year)->create(['department_id' => $unit->id]);

    expect($staff->can('update', $plan))->toBeTrue();
    $this->actingAs($staff)->withSession(['selected_academic_year_id' => $year->id])
        ->post(route('operational-plans.submit', ['current_team' => $staff->currentTeam, 'operational_plan' => $plan]))
        ->assertForbidden();

    $plan->update(['status' => 'approved']);
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create();
    $period = ReportingPeriod::factory()->open()->for($year)->create();
    $accomplishment = Accomplishment::factory()->for($item)->for($period)->create();
    Evidence::factory()->for($accomplishment)->create();
    expect($staff->can('update', $accomplishment))->toBeTrue();
    $this->post(route('monitoring.accomplishments.submit', [
        'current_team' => $staff->currentTeam, 'reporting_period' => $period,
        'plan_item' => $item, 'accomplishment' => $accomplishment,
    ]))->assertForbidden();
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('executive approval rejects own units and personal authorship even within an assigned branch', function () {
    $year = AcademicYear::factory()->open()->create();
    $area = OrganizationalUnit::factory()->create();
    $school = OrganizationalUnit::factory()->for($area, 'parent')->create();
    $vp = User::factory()->executive()->assignedToUnit($area)->create();
    $ownPlan = OperationalPlan::factory()->submitted()->for($year)->create(['department_id' => $area->id]);
    $subordinatePlan = OperationalPlan::factory()->submitted()->for($year)->create(['department_id' => $school->id]);

    expect($vp->can('approve', $subordinatePlan))->toBeTrue()
        ->and($vp->can('approve', $ownPlan))->toBeFalse();
    foreach (['created_by', 'submitted_by', 'accountable_user_id'] as $field) {
        $original = $subordinatePlan->getAttribute($field);
        $subordinatePlan->update([$field => $vp->id]);
        expect($vp->can('approve', $subordinatePlan))->toBeFalse();
        $subordinatePlan->update([$field => $original]);
    }
});

test('vp can view private evidence university-wide but cannot evaluate a sibling branch', function () {
    Storage::fake('local');
    $year = AcademicYear::factory()->open()->create();
    $academic = OrganizationalUnit::factory()->create();
    $finance = OrganizationalUnit::factory()->create();
    $vp = User::factory()->executive()->assignedToUnit($academic)->create();
    $plan = OperationalPlan::factory()->approved()->for($year)->create(['department_id' => $finance->id]);
    $item = PlanItem::factory()->for(KeyResultArea::factory()->for($plan))->create();
    $period = ReportingPeriod::factory()->open()->for($year)->create();
    $accomplishment = Accomplishment::factory()->readyForReview()->for($item)->for($period)->create();
    $evidence = Evidence::factory()->for($accomplishment)->create();
    Storage::disk('local')->put($evidence->stored_path, 'private evidence');
    AccomplishmentSubmission::factory()->for($accomplishment)->create();
    $parameters = ['current_team' => $vp->currentTeam, 'reporting_period' => $period,
        'plan_item' => $item, 'accomplishment' => $accomplishment];

    $this->actingAs($vp)->withSession(['selected_academic_year_id' => $year->id])
        ->get(route('monitoring.accomplishments.evidence.show', [...$parameters, 'evidence' => $evidence]))->assertOk();
    $this->post(route('evaluations.store', $parameters), ['decision' => 'accepted', 'rating' => 2])->assertForbidden();
    $this->assertDatabaseEmpty('evaluations');
});
