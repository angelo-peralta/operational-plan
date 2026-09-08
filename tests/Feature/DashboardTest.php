<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use App\Enums\OrganizationalUnitType;
use App\Enums\RemarkCategory;
use App\Enums\RemarkField;
use App\Enums\RemarkStatus;
use App\Enums\TeamRole;
use App\Enums\UserRole;
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
use App\Models\Role;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary', null));
});

test('department dashboard reports selected year plan monitoring and current rating metrics', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    $department = Department::factory()->create([
        'name' => 'Quality Assurance',
        'code' => 'QA',
    ]);
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $firstKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $secondKeyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $firstPlanItem = PlanItem::factory()->for($firstKeyResultArea)->create();
    $secondPlanItem = PlanItem::factory()->for($firstKeyResultArea)->create();
    $thirdPlanItem = PlanItem::factory()->for($secondKeyResultArea)->create();

    $awaitingReview = Accomplishment::factory()->readyForReview()
        ->for($firstPlanItem)->for($firstSemester)->create();
    Evidence::factory()->for($awaitingReview)->for($departmentUser, 'uploader')->create();
    AccomplishmentSubmission::factory()->for($awaitingReview)
        ->for($departmentUser, 'submitter')->create();
    AccomplishmentSubmission::factory()->for($awaitingReview)
        ->for($departmentUser, 'submitter')->create([
            'revision' => 2,
            'from_status' => AccomplishmentStatus::Returned->value,
        ]);

    $accepted = Accomplishment::factory()->accepted()
        ->for($secondPlanItem)->for($firstSemester)->create();
    Evidence::factory()->for($accepted)->for($departmentUser, 'uploader')->create();
    $acceptedSubmission = AccomplishmentSubmission::factory()->for($accepted)
        ->for($departmentUser, 'submitter')->create();
    Evaluation::factory()->for($acceptedSubmission, 'submission')->create([
        'decision' => EvaluationDecision::Accepted,
        'rating' => EvaluationRating::Exceeded,
    ]);

    $returned = Accomplishment::factory()->returned()
        ->for($firstPlanItem)->for($secondSemester)->create();
    Evidence::factory()->for($returned)->for($departmentUser, 'uploader')->create();
    $returnedSubmission = AccomplishmentSubmission::factory()->for($returned)
        ->for($departmentUser, 'submitter')->create();
    Evaluation::factory()->requestAdditionalEvidence()
        ->for($returnedSubmission, 'submission')->create();

    $rejected = Accomplishment::factory()->rejected()
        ->for($secondPlanItem)->for($secondSemester)->create();
    Evidence::factory()->for($rejected)->for($departmentUser, 'uploader')->create();
    $rejectedSubmission = AccomplishmentSubmission::factory()->for($rejected)
        ->for($departmentUser, 'submitter')->create();
    Evaluation::factory()->for($rejectedSubmission, 'submission')->create([
        'decision' => EvaluationDecision::Rejected,
        'rating' => EvaluationRating::SomehowMet,
        'remarks' => 'The evidence does not support the reported result.',
    ]);

    Accomplishment::factory()->for($thirdPlanItem)->for($firstSemester)->create();

    $remarkAuthor = User::factory()->reviewer()->create();
    $operationalPlan->remarks()->createMany([
        [
            'academic_year_id' => $academicYear->id,
            'author_id' => $remarkAuthor->id,
            'remarkable_type' => $operationalPlan->getMorphClass(),
            'remarkable_id' => $operationalPlan->id,
            'field_key' => RemarkField::Goal,
            'category' => RemarkCategory::Clarification,
            'content' => 'Clarify the intended outcome.',
            'status' => RemarkStatus::Open,
        ],
        [
            'academic_year_id' => $academicYear->id,
            'author_id' => $remarkAuthor->id,
            'remarkable_type' => $firstPlanItem->getMorphClass(),
            'remarkable_id' => $firstPlanItem->id,
            'field_key' => RemarkField::Strategy,
            'category' => RemarkCategory::Recommendation,
            'content' => 'The strategy was updated.',
            'status' => RemarkStatus::Addressed,
        ],
    ]);

    $response = $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.kind', 'unit')
        ->where('summary.organizationalUnit.id', $department->id)
        ->where('summary.organizationalUnit.name', 'Quality Assurance')
        ->where('summary.plan.id', $operationalPlan->id)
        ->where('summary.plan.status', 'approved')
        ->where('summary.plan.statusLabel', 'Approved')
        ->where('summary.plan.keyResultAreaCount', 2)
        ->where('summary.plan.planItemCount', 3)
        ->where('summary.pendingEvidence', 2)
        ->where('summary.accomplishments.awaitingReview', 1)
        ->where('summary.accomplishments.returned', 1)
        ->where('summary.accomplishments.accepted', 1)
        ->where('summary.accomplishments.rejected', 1)
        ->where('summary.remarks.total', 2)
        ->where('summary.remarks.open', 1)
        ->where('summary.remarks.addressed', 1)
        ->has('summary.reportingPeriods', 2)
        ->where('summary.reportingPeriods.0.id', $firstSemester->id)
        ->where('summary.reportingPeriods.0.submitted', 2)
        ->where('summary.reportingPeriods.0.awaitingReview', 1)
        ->where('summary.reportingPeriods.0.accepted', 1)
        ->where('summary.reportingPeriods.1.id', $secondSemester->id)
        ->where('summary.reportingPeriods.1.submitted', 2)
        ->where('summary.reportingPeriods.1.returned', 1)
        ->where('summary.reportingPeriods.1.rejected', 1)
        ->where('summary.evaluations.currentSubmissions', 4)
        ->where('summary.evaluations.evaluated', 3)
        ->where('summary.evaluations.awaitingReview', 1)
        ->where('summary.evaluations.historyCount', 3)
        ->where('summary.evaluations.completionRate', 75)
        ->where('summary.ratings.ratedCount', 1)
        ->where('summary.ratings.average', 3)
        ->where('summary.ratings.distribution.0.rating', 3)
        ->where('summary.ratings.distribution.0.count', 1)
        ->where('summary.ratings.distribution.2.rating', 1)
        ->where('summary.ratings.distribution.2.count', 0));
});

test('department dashboard never includes another department or academic year', function () {
    $this->withoutVite();
    $selectedAcademicYear = AcademicYear::factory()->current()->create();
    $selectedPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($selectedAcademicYear)->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($otherAcademicYear)->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();

    $selectedPlan = OperationalPlan::factory()->approved()
        ->for($selectedAcademicYear, 'academicYear')->for($department)->create();
    $selectedItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($selectedPlan))
        ->create();
    $selectedAccomplishment = Accomplishment::factory()->accepted()
        ->for($selectedItem)->for($selectedPeriod)->create();
    $selectedSubmission = AccomplishmentSubmission::factory()
        ->for($selectedAccomplishment)->for($departmentUser, 'submitter')->create();
    Evaluation::factory()->for($selectedSubmission, 'submission')->create([
        'rating' => EvaluationRating::Met,
    ]);

    $otherDepartmentPlan = OperationalPlan::factory()->approved()
        ->for($selectedAcademicYear, 'academicYear')->for($otherDepartment)->create();
    $otherDepartmentItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($otherDepartmentPlan))
        ->create();
    $otherDepartmentAccomplishment = Accomplishment::factory()->accepted()
        ->for($otherDepartmentItem)->for($selectedPeriod)->create();
    $otherDepartmentSubmission = AccomplishmentSubmission::factory()
        ->for($otherDepartmentAccomplishment)->create();
    Evaluation::factory()->for($otherDepartmentSubmission, 'submission')->create([
        'rating' => EvaluationRating::Exceeded,
    ]);

    $otherYearPlan = OperationalPlan::factory()->approved()
        ->for($otherAcademicYear, 'academicYear')->for($department)->create();
    $otherYearItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($otherYearPlan))
        ->create();
    $otherYearAccomplishment = Accomplishment::factory()->accepted()
        ->for($otherYearItem)->for($otherPeriod)->create();
    $otherYearSubmission = AccomplishmentSubmission::factory()
        ->for($otherYearAccomplishment)->for($departmentUser, 'submitter')->create();
    Evaluation::factory()->for($otherYearSubmission, 'submission')->create([
        'rating' => EvaluationRating::DidNotMeet,
        'remarks' => 'Requirements were not met.',
    ]);

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.plan.id', $selectedPlan->id)
            ->where('summary.reportingPeriods.0.submitted', 1)
            ->where('summary.accomplishments.accepted', 1)
            ->where('summary.ratings.ratedCount', 1)
            ->where('summary.ratings.average', 2));
});

test('reviewer dashboard reports active coverage and complete selected year historical pipelines', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $draftDepartment = Department::factory()->create();
    $submittedDepartment = Department::factory()->create();
    $approvedDepartment = Department::factory()->create();
    Department::factory()->create();
    $inactiveDepartment = Department::factory()->inactive()->create();
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();
    $submittedPlanAuthor = User::factory()->departmentUser()->forDepartment($submittedDepartment)->create();
    $approvedPlanAuthor = User::factory()->departmentUser()->forDepartment($approvedDepartment)->create();
    $inactivePlanAuthor = User::factory()->departmentUser()->forDepartment($inactiveDepartment)->create();

    OperationalPlan::factory()->for($academicYear, 'academicYear')->for($draftDepartment)->create();
    OperationalPlan::factory()->submitted()->for($academicYear, 'academicYear')->for($submittedDepartment)->create([
        'submitted_by' => $submittedPlanAuthor->id,
    ]);
    $approvedPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')->for($approvedDepartment)->create([
            'submitted_by' => $approvedPlanAuthor->id,
        ]);
    OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')->for($inactiveDepartment)->create([
            'submitted_by' => $inactivePlanAuthor->id,
        ]);

    $keyResultArea = KeyResultArea::factory()->for($approvedPlan)->create();
    $statuses = [
        AccomplishmentStatus::UnderReview,
        AccomplishmentStatus::Returned,
        AccomplishmentStatus::Accepted,
        AccomplishmentStatus::Rejected,
    ];
    $accomplishments = collect($statuses)->map(function (AccomplishmentStatus $status) use ($keyResultArea, $period): Accomplishment {
        $planItem = PlanItem::factory()->for($keyResultArea)->create();

        return Accomplishment::factory()->for($planItem)->for($period)->create([
            'status' => $status,
            'submitted_at' => now(),
        ]);
    });
    Accomplishment::factory()
        ->for(PlanItem::factory()->for($keyResultArea))
        ->for($period)
        ->create();
    Accomplishment::factory()->submitted()
        ->for(PlanItem::factory()->for($keyResultArea))
        ->for($period)
        ->create(['submitted_by' => $approvedPlanAuthor->id]);

    $acceptedSubmission = AccomplishmentSubmission::factory()
        ->for($accomplishments->get(2))->for($approvedPlanAuthor, 'submitter')->create();
    Evaluation::factory()->for($acceptedSubmission, 'submission')->create([
        'rating' => EvaluationRating::Exceeded,
    ]);
    $rejectedSubmission = AccomplishmentSubmission::factory()
        ->for($accomplishments->get(3))->for($approvedPlanAuthor, 'submitter')->create();
    Evaluation::factory()->for($rejectedSubmission, 'submission')->create([
        'decision' => EvaluationDecision::Rejected,
        'rating' => EvaluationRating::SomehowMet,
        'remarks' => 'The evidence was insufficient.',
    ]);

    $inactivePlan = OperationalPlan::query()
        ->whereBelongsTo($academicYear)
        ->whereBelongsTo($inactiveDepartment)
        ->firstOrFail();
    $inactiveItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($inactivePlan))
        ->create();
    $inactiveAccomplishment = Accomplishment::factory()->accepted()
        ->for($inactiveItem)->for($period)->create([
            'submitted_by' => $inactivePlanAuthor->id,
        ]);
    $inactiveSubmission = AccomplishmentSubmission::factory()
        ->for($inactiveAccomplishment)->for($inactivePlanAuthor, 'submitter')->create();
    Evaluation::factory()->for($inactiveSubmission, 'submission')->create([
        'rating' => EvaluationRating::DidNotMeet,
        'remarks' => 'Inactive department metric exclusion.',
    ]);

    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($otherAcademicYear)->create();
    $otherPlan = OperationalPlan::factory()->approved()
        ->for($otherAcademicYear, 'academicYear')
        ->for($draftDepartment)
        ->create(['submitted_by' => $submittedPlanAuthor->id]);
    $otherItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($otherPlan))
        ->create();
    $otherAccomplishment = Accomplishment::factory()->accepted()
        ->for($otherItem)->for($otherPeriod)->create([
            'submitted_by' => $submittedPlanAuthor->id,
        ]);
    $otherSubmission = AccomplishmentSubmission::factory()
        ->for($otherAccomplishment)->for($submittedPlanAuthor, 'submitter')->create();
    Evaluation::factory()->for($otherSubmission, 'submission')->create([
        'rating' => EvaluationRating::DidNotMeet,
        'remarks' => 'Other year metric exclusion.',
    ]);

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('summary.kind', 'oversight')
        ->where('summary.organizationalUnits.active', 4)
        ->where('summary.organizationalUnits.withPlan', 3)
        ->where('summary.organizationalUnits.withoutPlan', 1)
        ->where('summary.plans.total', 4)
        ->where('summary.plans.draft', 1)
        ->where('summary.plans.submitted', 1)
        ->where('summary.plans.approved', 2)
        ->where('summary.plans.returned', 0)
        ->where('summary.plans.closed', 0)
        ->where('summary.accomplishments.total', 5)
        ->where('summary.accomplishments.awaitingReview', 1)
        ->where('summary.accomplishments.returned', 1)
        ->where('summary.accomplishments.accepted', 2)
        ->where('summary.accomplishments.rejected', 1)
        ->where('summary.ratings.ratedCount', 2)
        ->where('summary.ratings.average', 1.5));
});

test('super admins receive the selected year oversight dashboard', function () {
    $this->withoutVite();
    $selectedAcademicYear = AcademicYear::factory()->current()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $superAdmin = User::factory()->superAdmin()->create();
    OperationalPlan::factory()
        ->for($selectedAcademicYear, 'academicYear')
        ->for($department)
        ->create();
    OperationalPlan::factory()
        ->for($otherAcademicYear, 'academicYear')
        ->for($department)
        ->create();

    $this->actingAs($superAdmin)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('dashboard', ['current_team' => $superAdmin->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.kind', 'oversight')
            ->where('summary.organizationalUnits.active', 1)
            ->where('summary.organizationalUnits.withPlan', 1)
            ->where('summary.organizationalUnits.withoutPlan', 0)
            ->where('summary.plans.total', 1));
});

test('oversight dashboard rejects both forms of cross year accomplishment linkage', function () {
    $this->withoutVite();
    $selectedAcademicYear = AcademicYear::factory()->current()->create();
    $selectedPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($selectedAcademicYear)->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($otherAcademicYear)->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();
    $selectedPlan = OperationalPlan::factory()->approved()
        ->for($selectedAcademicYear, 'academicYear')
        ->for($department)
        ->create();
    $otherPlan = OperationalPlan::factory()->approved()
        ->for($otherAcademicYear, 'academicYear')
        ->for($department)
        ->create();

    $selectedAccomplishment = Accomplishment::factory()->accepted()
        ->for(PlanItem::factory()->for(KeyResultArea::factory()->for($selectedPlan)))
        ->for($selectedPeriod)
        ->create();
    $selectedSubmission = AccomplishmentSubmission::factory()
        ->for($selectedAccomplishment)
        ->for($departmentUser, 'submitter')
        ->create();
    Evaluation::factory()->for($selectedSubmission, 'submission')->create([
        'rating' => EvaluationRating::Met,
    ]);

    $selectedPlanForeignPeriodAccomplishment = Accomplishment::factory()->accepted()
        ->for(PlanItem::factory()->for(KeyResultArea::factory()->for($selectedPlan)))
        ->for($otherPeriod)
        ->create();
    $selectedPlanForeignPeriodSubmission = AccomplishmentSubmission::factory()
        ->for($selectedPlanForeignPeriodAccomplishment)
        ->for($departmentUser, 'submitter')
        ->create();
    Evaluation::factory()->for($selectedPlanForeignPeriodSubmission, 'submission')->create([
        'rating' => EvaluationRating::Exceeded,
    ]);

    $foreignPlanSelectedPeriodAccomplishment = Accomplishment::factory()->accepted()
        ->for(PlanItem::factory()->for(KeyResultArea::factory()->for($otherPlan)))
        ->for($selectedPeriod)
        ->create();
    $foreignPlanSelectedPeriodSubmission = AccomplishmentSubmission::factory()
        ->for($foreignPlanSelectedPeriodAccomplishment)
        ->for($departmentUser, 'submitter')
        ->create();
    Evaluation::factory()->for($foreignPlanSelectedPeriodSubmission, 'submission')->create([
        'rating' => EvaluationRating::Exceeded,
    ]);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $selectedAcademicYear->id])
        ->get(route('dashboard', ['current_team' => $reviewer->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.plans.total', 1)
            ->where('summary.accomplishments.total', 1)
            ->where('summary.accomplishments.accepted', 1)
            ->where('summary.ratings.ratedCount', 1)
            ->where('summary.ratings.average', 2));
});

test('pending evidence counts monitored plan item period obligations without an uploaded file', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $firstSemester = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondSemester = ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    ReportingPeriod::factory()->for($academicYear)->create([
        'name' => 'Supplemental Period',
        'code' => 'supplemental_period',
        'sequence' => 3,
    ]);
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $firstPlanItem = PlanItem::factory()->for($keyResultArea)->create();
    $secondPlanItem = PlanItem::factory()->for($keyResultArea)->create();

    $firstSemesterAccomplishment = Accomplishment::factory()
        ->for($firstPlanItem)
        ->for($firstSemester)
        ->create();
    Evidence::factory()->count(2)
        ->for($firstSemesterAccomplishment)
        ->for($departmentUser, 'uploader')
        ->create();
    Accomplishment::factory()
        ->for($firstPlanItem)
        ->for($secondSemester)
        ->create();
    $secondSemesterAccomplishment = Accomplishment::factory()
        ->for($secondPlanItem)
        ->for($secondSemester)
        ->create();
    Evidence::factory()
        ->for($secondSemesterAccomplishment)
        ->for($departmentUser, 'uploader')
        ->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('summary.reportingPeriods', 3)
            ->where('summary.plan.planItemCount', 2)
            ->where('summary.pendingEvidence', 2));
});

test('pending evidence remains zero until the operational plan is approved', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    PlanItem::factory()->for(KeyResultArea::factory()->for($operationalPlan))->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.plan.status', 'draft')
            ->where('summary.pendingEvidence', 0));
});

test('semester submissions include legacy accomplishments without immutable history', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $reportingPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $planItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($operationalPlan))
        ->create();
    $legacyAccomplishment = Accomplishment::factory()->submitted()
        ->for($planItem)
        ->for($reportingPeriod)
        ->create(['submitted_by' => $departmentUser->id]);
    Evidence::factory()
        ->for($legacyAccomplishment)
        ->for($departmentUser, 'uploader')
        ->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.reportingPeriods.0.submitted', 1)
            ->where('summary.pendingEvidence', 0));
});

test('department users without an assignment receive no institutional dashboard data', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $departmentUser = User::factory()->create(['department_id' => null]);
    OperationalPlan::factory()->for($academicYear, 'academicYear')->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.kind', 'unit')
            ->where('summary.organizationalUnit', null)
            ->where('summary.plan', null)
            ->where('summary.reportingPeriods', [])
            ->where('summary.pendingEvidence', 0)
            ->where('summary.ratings.ratedCount', 0));
});

test('custom reviewer access profiles receive the oversight dashboard', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $customReviewerRole = Role::factory()->create([
        'name' => 'Quality Reviewer',
        'slug' => 'quality-reviewer',
        'access_level' => UserRole::Executive,
    ]);
    $reviewer = User::factory()->forOperationalRole($customReviewerRole)->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $reviewer->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.kind', 'oversight')
            ->where('summary.organizationalUnits.active', 0)
            ->where('summary.plans.total', 0)
            ->where('summary.accomplishments.total', 0)
            ->where('summary.ratings.average', null));
});

test('department dashboard shows an empty plan state without exposing another department', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    ReportingPeriod::factory()->closed()->secondSemester()->for($academicYear)->create();
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($otherDepartment)
        ->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.kind', 'unit')
            ->where('summary.organizationalUnit.id', $department->id)
            ->where('summary.plan', null)
            ->has('summary.reportingPeriods', 2)
            ->where('summary.reportingPeriods.0.submitted', 0)
            ->where('summary.reportingPeriods.1.submitted', 0)
            ->where('summary.pendingEvidence', 0)
            ->where('summary.accomplishments.accepted', 0)
            ->where('summary.ratings.ratedCount', 0));
});

test('dashboard rating summaries use only the latest immutable submission revision', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->current()->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $department = Department::factory()->create();
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $planItem = PlanItem::factory()
        ->for(KeyResultArea::factory()->for($operationalPlan))
        ->create();
    $accomplishment = Accomplishment::factory()->accepted()
        ->for($planItem)
        ->for($period)
        ->create();
    $firstSubmission = AccomplishmentSubmission::factory()
        ->for($accomplishment)
        ->for($departmentUser, 'submitter')
        ->create();
    $secondSubmission = AccomplishmentSubmission::factory()
        ->for($accomplishment)
        ->for($departmentUser, 'submitter')
        ->create(['revision' => 2]);
    Evaluation::factory()->for($firstSubmission, 'submission')->create([
        'rating' => EvaluationRating::DidNotMeet,
        'remarks' => 'Superseded historical rating.',
    ]);
    Evaluation::factory()->for($secondSubmission, 'submission')->create([
        'rating' => EvaluationRating::Exceeded,
    ]);

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.ratings.ratedCount', 1)
            ->where('summary.ratings.average', 3)
            ->where('summary.ratings.distribution.0.rating', 3)
            ->where('summary.ratings.distribution.0.count', 1)
            ->where('summary.ratings.distribution.3.rating', 0)
            ->where('summary.ratings.distribution.3.count', 0));
});

test('dashboard rating summaries use the Presidential evaluation as the effective rating', function () {
    $this->withoutVite();

    $academicYear = AcademicYear::factory()->current()->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $university = OrganizationalUnit::factory()->university()->create();
    $vicePresidentialArea = OrganizationalUnit::factory()->create([
        'parent_id' => $university->id,
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);
    $department = Department::factory()->create(['parent_id' => $vicePresidentialArea->id]);
    $departmentUser = User::factory()->departmentUser()->forDepartment($department)->create();
    $vicePresident = User::factory()->executive()->assignedToUnit($vicePresidentialArea)->create();
    $president = User::factory()->executive()->assignedToUnit($university)->create();
    $operationalPlan = OperationalPlan::factory()->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create();
    $accomplishment = Accomplishment::factory()->accepted()
        ->for(PlanItem::factory()->for(KeyResultArea::factory()->for($operationalPlan)))
        ->for($period)
        ->create();
    $submission = AccomplishmentSubmission::factory()
        ->for($accomplishment)
        ->for($departmentUser, 'submitter')
        ->create();
    $vicePresidentialEvaluation = Evaluation::factory()
        ->for($submission, 'submission')
        ->for($vicePresident, 'reviewer')
        ->for($vicePresidentialArea, 'organizationalUnit')
        ->create(['rating' => EvaluationRating::SomehowMet, 'remarks' => 'VP rating.']);
    Evaluation::factory()
        ->for($submission, 'submission')
        ->for($president, 'reviewer')
        ->for($university, 'organizationalUnit')
        ->create([
            'rating' => EvaluationRating::Exceeded,
            'is_override' => true,
            'overrides_evaluation_id' => $vicePresidentialEvaluation->id,
            'remarks' => 'Presidential override.',
        ]);

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('summary.ratings.ratedCount', 1)
            ->where('summary.ratings.average', 3)
            ->where('summary.ratings.distribution.0.count', 1)
            ->where('summary.ratings.distribution.2.count', 0)
            ->where('summary.evaluations.evaluated', 1)
            ->where('summary.evaluations.historyCount', 2));
});

test('dashboard includes pending invitations for the authenticated user', function () {
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'SPUP-OPS Team']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 1)
        ->where('pendingInvitations.0.code', $invitation->code)
        ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
        ->where('pendingInvitations.0.team.name', 'SPUP-OPS Team')
        ->where('pendingInvitations.0.team.slug', $team->slug)
        ->missing('pendingInvitations.0.teamName'),
    );
});

test('dashboard does not include accepted invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );
});

test('dashboard excludes expired invitations without deleting them', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});

test('dashboard does not include or delete other users invitations', function () {
    $owner = User::factory()->create();
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $response = $this
        ->actingAs($invitedUser)
        ->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->has('pendingInvitations', 0),
    );

    $this->assertDatabaseHas('team_invitations', [
        'id' => $invitation->id,
    ]);
});
