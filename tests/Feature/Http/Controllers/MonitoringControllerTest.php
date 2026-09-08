<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('department users see only their approved plan in the selected academic year and reporting period', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $user = User::factory()->officeHead()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $otherPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($otherAcademicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create(['kpi_target_text' => 'Train 20 staff']);
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create([
        'accomplishment_text' => 'Trained 10 staff',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for(Department::factory())->create();
    OperationalPlan::factory()->approved()->for($otherAcademicYear, 'academicYear')->for($department)->create();

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period->id,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('monitoring/index')
        ->where('academicYear.id', $academicYear->id)
        ->where('selectedReportingPeriod.id', $period->id)
        ->has('reportingPeriods', 1)
        ->has('operationalPlans', 1)
        ->where('operationalPlans.0.id', $plan->id)
        ->where('operationalPlans.0.organizationalUnit.id', $department->id)
        ->missing('operationalPlans.0.department')
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.id', $planItem->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.id', $accomplishment->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.createdBy.id', $user->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.updatedBy.id', $user->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.updateAccomplishment', true)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.submitAccomplishment', true)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionBlocker', 'Upload at least one evidence file before submitting.'));

    expect($otherPeriod->academic_year_id)->toBe($otherAcademicYear->id);
});

test('monitoring exposes immutable submission history and resubmission readiness', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $user = User::factory()->officeHead()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->returned()->for($planItem)->for($period)->create([
        'submitted_by' => $user->id,
        'submitted_at' => '2026-09-01 08:00:00',
    ]);
    $evidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create();
    $submission = AccomplishmentSubmission::factory()->for($accomplishment)->for($user, 'submitter')->create([
        'revision' => 1,
        'from_status' => AccomplishmentStatus::Draft->value,
        'to_status' => AccomplishmentStatus::UnderReview->value,
        'evidence_count' => 1,
        'submitted_at' => '2026-09-01 08:00:00',
    ]);
    $reviewer = User::factory()->reviewer()->create(['name' => 'Planning Reviewer']);
    $evaluation = Evaluation::factory()
        ->requestAdditionalEvidence()
        ->for($submission, 'submission')
        ->for($reviewer, 'reviewer')
        ->create([
            'remarks' => 'Please attach the signed completion report.',
            'reviewed_at' => '2026-09-02 10:30:00',
            'created_at' => '2026-09-02 10:30:00',
        ]);
    $remark = $accomplishment->remarks()->create([
        'academic_year_id' => $academicYear->id,
        'operational_plan_id' => $plan->id,
        'author_id' => $reviewer->id,
        'field_key' => 'accomplishment_text',
        'reporting_period_id' => $period->id,
        'category' => 'clarification',
        'content' => 'Explain the reported result.',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period->id,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.updateAccomplishment', true)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.submitAccomplishment', true)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionBlocker', 'Upload at least one additional evidence file before resubmitting.')
        ->has('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.id', $submission->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.revision', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.fromStatus', AccomplishmentStatus::Draft->value)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.toStatus', AccomplishmentStatus::UnderReview->value)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.evidenceCount', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.submissionHistory.0.submitter.name', $user->name)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evidence.0.checksum', $evidence->checksum)
        ->missing('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evidence.0.storedPath')
        ->missing('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evidence.0.stored_path')
        ->has('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.remarks', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.remarks.0.id', $remark->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.remarks.0.targetType', 'accomplishment')
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.remarks.0.canAddress', true)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.createRemark', false)
        ->has('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.id', $evaluation->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.submissionRevision', 1)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.decision', EvaluationDecision::RequestAdditionalEvidence->value)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.rating', null)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.remarks', 'Please attach the signed completion report.')
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.evaluationHistory.0.reviewer.name', 'Planning Reviewer'));
});

test('reviewers can monitor all departments but cannot author accomplishment drafts', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();

    foreach (range(1, 2) as $sortOrder) {
        $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for(Department::factory())->create();
        $keyResultArea = KeyResultArea::factory()->for($plan)->create(['sort_order' => $sortOrder]);
        PlanItem::factory()->for($keyResultArea)->create();
    }

    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('monitoring/index')
        ->has('operationalPlans', 2)
        ->where('selectedReportingPeriod.id', $period->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false)
        ->where('operationalPlans.1.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false));
});

test('closed academic year monitoring remains visible but read only', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->closed()->create();
    $department = Department::factory()->create();
    $user = User::factory()->officeHead()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->closed()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
        ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('academicYear.id', $academicYear->id)
        ->where('operationalPlans.0.id', $plan->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.id', $accomplishment->id)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.createAccomplishment', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.permissions.updateAccomplishment', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.uploadEvidence', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.submitAccomplishment', false)
        ->where('operationalPlans.0.keyResultAreas.0.planItems.0.accomplishment.permissions.createRemark', false));
});

test('monitoring rejects reporting periods from another academic year with 404', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->for($otherAcademicYear)->create();

    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', [
            'current_team' => $reviewer->currentTeam,
            'reporting_period' => $otherPeriod->id,
        ]));

    $response->assertNotFound();
});

test('monitoring excludes draft and submitted plans', function (OperationalPlanStatus $status) {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    ReportingPeriod::factory()->open()->for($academicYear)->create();
    OperationalPlan::factory()->for($academicYear, 'academicYear')->for(Department::factory())->create([
        'status' => $status,
    ]);

    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('monitoring.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page->has('operationalPlans', 0));
})->with([
    'draft' => OperationalPlanStatus::Draft,
    'submitted' => OperationalPlanStatus::Submitted,
]);
