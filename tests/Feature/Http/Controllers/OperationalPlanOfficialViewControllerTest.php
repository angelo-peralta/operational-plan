<?php

use App\Enums\EvaluationRating;
use App\Enums\OrganizationalUnitType;
use App\Enums\RemarkCategory;
use App\Enums\RemarkField;
use App\Enums\RemarkStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
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
    $planItem->coAccountableOrganizationalUnits()->attach($coAccountableDepartment);

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
        ->where('operationalPlan.organizationalUnit.code', 'CPRINT')
        ->where('operationalPlan.accountablePosition', 'CPRINT Director')
        ->where('operationalPlan.goal', 'Integrate ethical AI and digital technologies.')
        ->has('operationalPlan.keyResultAreas', 1)
        ->where('operationalPlan.keyResultAreas.0.code', 'KRA 1')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.strategy', 'Develop AI Usage Guidelines')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.documentaryEvidenceRequirements.0', 'Approved AI Guidelines')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.manualCoAccountableUnits.0', 'VPA')
        ->where('operationalPlan.keyResultAreas.0.planItems.0.coAccountableOrganizationalUnits.0.code', 'ICT'));
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

test('official view prints effective semester ratings and preserves the executive remarks summary', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->current()->create();
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    $university = OrganizationalUnit::factory()->university()->create();
    $vicePresidentialArea = OrganizationalUnit::factory()->create([
        'parent_id' => $university->id,
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);
    $department = Department::factory()->create(['parent_id' => $vicePresidentialArea->id]);
    $staff = User::factory()->departmentUser()->forDepartment($department)->create();
    $vicePresident = User::factory()->executive()->assignedToUnit($vicePresidentialArea)->create();
    $president = User::factory()->executive()->assignedToUnit($university)->create();
    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create([
        'code' => 'KRA 2',
        'name' => 'Research',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Publish institutional research.',
    ]);
    $firstAccomplishment = Accomplishment::factory()->accepted()
        ->for($planItem)
        ->for($firstSemester)
        ->create();
    $firstSubmission = AccomplishmentSubmission::factory()
        ->for($firstAccomplishment)
        ->for($staff, 'submitter')
        ->create();
    $vicePresidentialEvaluation = Evaluation::factory()
        ->for($firstSubmission, 'submission')
        ->for($vicePresident, 'reviewer')
        ->for($vicePresidentialArea, 'organizationalUnit')
        ->create([
            'rating' => EvaluationRating::SomehowMet,
            'remarks' => 'The initial evidence only partly met the target.',
        ]);
    Evaluation::factory()
        ->for($firstSubmission, 'submission')
        ->for($president, 'reviewer')
        ->for($university, 'organizationalUnit')
        ->create([
            'rating' => EvaluationRating::Exceeded,
            'remarks' => 'The consolidated evidence exceeded the target.',
            'is_override' => true,
            'overrides_evaluation_id' => $vicePresidentialEvaluation->id,
        ]);
    $secondAccomplishment = Accomplishment::factory()->accepted()
        ->for($planItem)
        ->for($secondSemester)
        ->create();
    $secondSubmission = AccomplishmentSubmission::factory()
        ->for($secondAccomplishment)
        ->for($staff, 'submitter')
        ->create();
    Evaluation::factory()
        ->for($secondSubmission, 'submission')
        ->for($vicePresident, 'reviewer')
        ->for($vicePresidentialArea, 'organizationalUnit')
        ->create(['rating' => EvaluationRating::Met]);

    $operationalPlan->remarks()->create([
        'academic_year_id' => $academicYear->id,
        'author_id' => $vicePresident->id,
        'remarkable_type' => $planItem->getMorphClass(),
        'remarkable_id' => $planItem->id,
        'field_key' => RemarkField::Strategy,
        'category' => RemarkCategory::Clarification,
        'content' => 'Clarify the publication venue.',
        'status' => RemarkStatus::Addressed,
    ]);

    $this->actingAs($staff)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('operational-plans.official', [
            'current_team' => $staff->currentTeam,
            'operational_plan' => $operationalPlan,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('report.itemResults', 1)
            ->where('report.itemResults.0.planItemId', $planItem->id)
            ->where('report.itemResults.0.firstSemester.rating', 3)
            ->where('report.itemResults.0.firstSemester.isPresidential', true)
            ->where('report.itemResults.0.firstSemester.isOverride', true)
            ->where('report.itemResults.0.firstSemester.remarks', 'The consolidated evidence exceeded the target.')
            ->where('report.itemResults.0.secondSemester.rating', 2)
            ->where('report.itemResults.0.secondSemester.isPresidential', false)
            ->has('report.executiveRemarks', 1)
            ->where('report.executiveRemarks.0.author.authority', 'Vice President')
            ->where('report.executiveRemarks.0.field', 'strategy')
            ->where('report.executiveRemarks.0.status', 'addressed'));
});
