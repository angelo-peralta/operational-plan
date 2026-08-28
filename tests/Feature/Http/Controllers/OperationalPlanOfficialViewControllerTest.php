<?php

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('department users render their historical official plan in the selected academic year', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->closed()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $department = Department::factory()->create([
        'name' => 'Center for Planning, Research, Innovations, and New Technologies',
        'code' => 'CPRINT',
    ]);
    $coAccountableDepartment = Department::factory()->create([
        'name' => 'Information and Communications Technology',
        'code' => 'ICT',
    ]);
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create([
            'accountable_name' => null,
            'accountable_position' => 'CPRINT Director',
            'goal' => 'Integrate ethical AI and digital technologies.',
        ]);
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create([
        'code' => 'KRA 1',
        'name' => 'Quality Teaching, Learning, and Student Services',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Use AI ethically in teaching and learning.',
        'strategy' => 'Develop AI Usage Guidelines',
        'kpi_target_text' => 'Institutional research-based AI guidelines are approved.',
        'resources_needed' => 'AI Policy Development and Research Team',
        'documentary_evidence_requirements' => ['Approved AI Guidelines'],
        'manual_co_accountable_units' => ['VPA'],
    ]);
    $planItem->coAccountableDepartments()->attach($coAccountableDepartment);

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('operational-plans.official', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $operationalPlan,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('operational-plans/official')
        ->where('institutionName', config('app.name'))
        ->where('operationalPlan.academicYear.name', 'AY 2025-2026')
        ->where('operationalPlan.department.code', 'CPRINT')
        ->where('operationalPlan.accountablePosition', 'CPRINT Director')
        ->where('operationalPlan.goal', 'Integrate ethical AI and digital technologies.')
        ->has('operationalPlan.keyResultAreas', 1)
        ->where('operationalPlan.keyResultAreas.0.code', 'KRA 1')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.strategy', 'Develop AI Usage Guidelines')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.documentaryEvidenceRequirements.0', 'Approved AI Guidelines')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.manualCoAccountableUnits.0', 'VPA')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.coAccountableDepartments.0.code', 'ICT'));
});

test('official view returns 404 for another department plan', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->open()->create();
    $departmentUser = User::factory()->departmentUser()->create();
    $otherPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for(Department::factory())
        ->create();

    $response = $this
        ->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('operational-plans.official', [
            'current_team' => $departmentUser->currentTeam,
            'operational_plan' => $otherPlan,
        ]));

    $response->assertNotFound();
});

test('official view returns 404 outside the selected academic year', function () {
    $this->withoutVite();

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
        ->get(route('operational-plans.official', [
            'current_team' => $administrator->currentTeam,
            'operational_plan' => $otherYearPlan,
        ]));

    $response->assertNotFound();
});
