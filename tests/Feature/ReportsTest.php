<?php

use App\Enums\EvaluationDecision;
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
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('vice president reports consolidate university-wide units in the selected academic year', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->current()->create(['name' => 'AY 2026-2027']);
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    $university = OrganizationalUnit::factory()->university()->create();
    $vicePresidentialArea = OrganizationalUnit::factory()->create([
        'parent_id' => $university->id,
        'type' => OrganizationalUnitType::VicePresidentialArea,
        'name' => 'Academic Affairs',
    ]);
    $office = Department::factory()->create([
        'parent_id' => $vicePresidentialArea->id,
        'name' => 'Quality Office',
        'code' => 'QO',
    ]);
    $otherVicePresidentialArea = OrganizationalUnit::factory()->create([
        'parent_id' => $university->id,
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);
    $otherOffice = Department::factory()->create(['parent_id' => $otherVicePresidentialArea->id]);
    $vicePresident = User::factory()->executive()->assignedToUnit($vicePresidentialArea)->create();
    $president = User::factory()->executive()->assignedToUnit($university)->create();
    $staff = User::factory()->departmentUser()->forDepartment($office)->create();

    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($office, 'organizationalUnit')
        ->create([
            'submitted_by' => $staff->id,
            'approved_by' => $vicePresident->id,
        ]);
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create([
        'code' => 'KRA 1',
        'name' => 'Institutional Quality',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Complete the annual quality review.',
    ]);
    $acceptedAccomplishment = Accomplishment::factory()->accepted()
        ->for($planItem)
        ->for($firstSemester)
        ->create(['submitted_by' => $staff->id]);
    Evidence::factory()->for($acceptedAccomplishment)->for($staff, 'uploader')->create();
    $acceptedSubmission = AccomplishmentSubmission::factory()
        ->for($acceptedAccomplishment)
        ->for($staff, 'submitter')
        ->create();
    $vicePresidentialEvaluation = Evaluation::factory()
        ->for($acceptedSubmission, 'submission')
        ->for($vicePresident, 'reviewer')
        ->for($vicePresidentialArea, 'organizationalUnit')
        ->create([
            'decision' => EvaluationDecision::Accepted,
            'rating' => EvaluationRating::Met,
        ]);
    Evaluation::factory()
        ->for($acceptedSubmission, 'submission')
        ->for($president, 'reviewer')
        ->for($university, 'organizationalUnit')
        ->create([
            'decision' => EvaluationDecision::Accepted,
            'rating' => EvaluationRating::Exceeded,
            'is_override' => true,
            'overrides_evaluation_id' => $vicePresidentialEvaluation->id,
            'remarks' => 'University evidence supports the higher result.',
        ]);
    $awaitingAccomplishment = Accomplishment::factory()->readyForReview()
        ->for($planItem)
        ->for($secondSemester)
        ->create(['submitted_by' => $staff->id]);
    AccomplishmentSubmission::factory()
        ->for($awaitingAccomplishment)
        ->for($staff, 'submitter')
        ->create();

    $operationalPlan->remarks()->create([
        'academic_year_id' => $academicYear->id,
        'author_id' => $president->id,
        'remarkable_type' => $planItem->getMorphClass(),
        'remarkable_id' => $planItem->id,
        'field_key' => RemarkField::Objective,
        'reporting_period_id' => $firstSemester->id,
        'category' => RemarkCategory::Recommendation,
        'content' => 'State the expected institutional outcome.',
        'status' => RemarkStatus::Open,
    ]);

    $otherPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($otherOffice, 'organizationalUnit')
        ->create([
            'submitted_by' => $staff->id,
            'approved_by' => $president->id,
        ]);
    $otherItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($otherPlan))
        ->create();
    $otherAccomplishment = Accomplishment::factory()->accepted()
        ->for($otherItem)
        ->for($firstSemester)
        ->create(['submitted_by' => $staff->id]);
    $otherSubmission = AccomplishmentSubmission::factory()
        ->for($otherAccomplishment)
        ->for($staff, 'submitter')
        ->create();
    Evaluation::factory()->for($otherSubmission, 'submission')->create([
        'rating' => EvaluationRating::DidNotMeet,
        'remarks' => 'University-wide report metric.',
    ]);

    $response = $this->actingAs($vicePresident)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('reports.index', ['current_team' => $vicePresident->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('reports/index')
        ->where('report.academicYear.id', $academicYear->id)
        ->where('report.mode', 'annual')
        ->where('report.selectedReportingPeriod', null)
        ->has('report.organizationalUnits', 4)
        ->where('report.summary.organizationalUnits.active', 4)
        ->where('report.summary.organizationalUnits.withPlan', 2)
        ->where('report.summary.plans.total', 2)
        ->where('report.summary.planItemCount', 2)
        ->where('report.summary.accomplishments.total', 3)
        ->where('report.summary.accomplishments.awaitingReview', 1)
        ->where('report.summary.accomplishments.accepted', 2)
        ->where('report.summary.pendingEvidence', 3)
        ->where('report.summary.remarks.open', 1)
        ->where('report.summary.evaluations.currentSubmissions', 3)
        ->where('report.summary.evaluations.evaluated', 2)
        ->where('report.summary.evaluations.historyCount', 3)
        ->where('report.summary.evaluations.completionRate', 66.7)
        ->where('report.summary.ratings.ratedCount', 2)
        ->where('report.summary.ratings.average', 1.5)
        ->has('report.remarks', 1)
        ->where('report.remarks.0.author.authority', 'President')
        ->where('report.remarks.0.keyResultArea.code', 'KRA 1')
        ->where('report.remarks.0.planItem.id', $planItem->id)
        ->where('report.remarks.0.field', 'objective')
        ->where('report.remarks.0.status', 'open'));

    $this->actingAs($vicePresident)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('reports.index', [
            'current_team' => $vicePresident->currentTeam,
            'reporting_period' => $firstSemester->id,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('report.mode', 'monitoring')
            ->where('report.selectedReportingPeriod.id', $firstSemester->id)
            ->where('report.summary.accomplishments.total', 2)
            ->where('report.summary.accomplishments.awaitingReview', 0)
            ->where('report.summary.pendingEvidence', 1)
            ->where('report.summary.ratings.average', 1.5)
            ->has('report.remarks', 1));
});

test('reports reject unassigned users and reporting periods outside the selected academic year', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->current()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->for($otherAcademicYear)->create();
    $unassignedStaff = User::factory()->create(['department_id' => null]);

    $this->actingAs($unassignedStaff)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('reports.index', ['current_team' => $unassignedStaff->currentTeam]))
        ->assertForbidden();

    $administrator = User::factory()->systemAdmin()->create();

    $this->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('reports.index', [
            'current_team' => $administrator->currentTeam,
            'reporting_period' => $otherPeriod->id,
        ]))
        ->assertNotFound();
});
