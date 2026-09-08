<?php

use App\Actions\Accomplishments\RecordAccomplishmentSubmission;
use App\Enums\AccomplishmentStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array<string, mixed>
 */
function evaluationQueueContext(
    ?AcademicYear $academicYear = null,
    ?ReportingPeriod $period = null,
    ?Department $department = null,
    ?Carbon $submittedAt = null,
    AccomplishmentStatus $status = AccomplishmentStatus::UnderReview,
): array {
    $academicYear ??= AcademicYear::factory()->open()->create([
        'name' => 'AY 2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
    ]);
    $period ??= ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create([
        'name' => 'First Semester',
        'code' => 'first_semester',
        'sequence' => 1,
    ]);
    $department ??= Department::factory()->create();
    $author = User::factory()->departmentUser()->forDepartment($department)->create([
        'name' => 'Department Author',
    ]);
    $plan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create([
            'goal' => 'Strengthen institutional quality.',
            'accountable_name' => 'Quality Director',
            'accountable_position' => 'Director',
        ]);
    $keyResultArea = KeyResultArea::factory()->for($plan)->create([
        'code' => 'KRA 1',
        'name' => 'Quality Outcomes',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Complete program evaluations.',
        'strategy' => 'Conduct semester reviews.',
        'kpi_target_text' => 'Evaluate 100% of active programs.',
        'target_value' => 100,
        'target_unit' => 'percent',
        'target_operator' => 'at_least',
        'target_frequency' => 'semester',
        'resources_needed' => 'Evaluation tools',
        'documentary_evidence_requirements' => ['Evaluation report', 'Signed attendance'],
    ]);
    $submittedAt ??= Carbon::parse('2026-09-04 09:00:00');
    $accomplishment = Accomplishment::factory()
        ->for($planItem)
        ->for($period)
        ->create([
            'status' => $status,
            'submitted_by' => $author->id,
            'submitted_at' => $submittedAt,
            'reported_value' => 95,
            'accomplishment_text' => 'Nineteen of twenty programs were evaluated.',
            'percentage_accomplished' => 95,
        ]);
    $evidence = Evidence::factory()->for($accomplishment)->for($author, 'uploader')->create([
        'title' => 'Program evaluation report',
        'description' => 'Signed report for the semester.',
        'original_filename' => 'evaluation-report.pdf',
        'mime_type' => 'application/pdf',
        'file_size' => 4096,
        'stored_path' => "evidence/private-{$accomplishment->id}.pdf",
        'checksum' => hash('sha256', (string) $accomplishment->id),
    ]);

    $submission = app(RecordAccomplishmentSubmission::class)->handle(
        $accomplishment,
        $author,
        AccomplishmentStatus::Draft,
        AccomplishmentStatus::UnderReview,
        1,
        $submittedAt,
    );

    return compact(
        'academicYear',
        'period',
        'department',
        'author',
        'plan',
        'keyResultArea',
        'planItem',
        'accomplishment',
        'evidence',
        'submission',
    );
}

function evaluationQueueRoute(User $user, ?ReportingPeriod $period = null): string
{
    $parameters = ['current_team' => $user->currentTeam];

    if ($period instanceof ReportingPeriod) {
        $parameters['reporting_period'] = $period;
    }

    return route('evaluations.index', $parameters);
}

test('reviewer queue exposes complete immutable review context without private storage data', function () {
    $this->withoutVite();
    $context = evaluationQueueContext();
    extract($context);
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($reviewer, $period));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('evaluations/index')
        ->where('academicYear.id', $academicYear->id)
        ->where('selectedReportingPeriod.id', $period->id)
        ->has('ratingOptions', 4)
        ->has('queue', 1)
        ->where('queue.0.id', $accomplishment->id)
        ->where('queue.0.academicYear.name', 'AY 2026-2027')
        ->where('queue.0.reportingPeriod.name', 'First Semester')
        ->where('queue.0.organizationalUnit.code', $department->code)
        ->where('queue.0.operationalPlan.goal', 'Strengthen institutional quality.')
        ->where('queue.0.keyResultArea.code', 'KRA 1')
        ->where('queue.0.planItem.objective', 'Complete program evaluations.')
        ->where('queue.0.planItem.strategy', 'Conduct semester reviews.')
        ->where('queue.0.planItem.kpiTargetText', 'Evaluate 100% of active programs.')
        ->where('queue.0.planItem.documentaryEvidenceRequirements', ['Evaluation report', 'Signed attendance'])
        ->where('queue.0.accomplishment.reportedValue', '95.0000')
        ->where('queue.0.accomplishment.accomplishmentText', 'Nineteen of twenty programs were evaluated.')
        ->where('queue.0.submission.id', $submission->id)
        ->where('queue.0.submission.revision', 1)
        ->where('queue.0.submission.submitter.name', 'Department Author')
        ->where('queue.0.evidence.0.id', $evidence->id)
        ->where('queue.0.evidence.0.originalFilename', 'evaluation-report.pdf')
        ->where('queue.0.evidence.0.checksum', $evidence->checksum)
        ->where('queue.0.permissions.reviewAccomplishment', true)
        ->missing('queue.0.evidence.0.storedPath')
        ->missing('queue.0.evidence.0.stored_path'));
});

test('queue is scoped to the selected academic year and reporting period', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $firstPeriod = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $secondPeriod = ReportingPeriod::factory()->open()->secondSemester()->for($academicYear)->create();
    $selected = evaluationQueueContext($academicYear, $firstPeriod);
    evaluationQueueContext($academicYear, $secondPeriod);
    evaluationQueueContext(AcademicYear::factory()->open()->create([
        'name' => 'AY 2028-2029',
        'start_year' => 2028,
        'end_year' => 2029,
    ]));
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($reviewer, $firstPeriod))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.id', $selected['accomplishment']->id));
});

test('queue excludes accomplishments that are not ready for review', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $ready = evaluationQueueContext($academicYear, $period);

    foreach (array_filter(
        AccomplishmentStatus::cases(),
        fn (AccomplishmentStatus $status): bool => $status !== AccomplishmentStatus::UnderReview,
    ) as $status) {
        evaluationQueueContext($academicYear, $period, status: $status);
    }

    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($reviewer, $period))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.id', $ready['accomplishment']->id));
});

test('queue rejects invalid and cross year reporting period filters', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    ReportingPeriod::factory()->open()->for($academicYear)->create();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->for($otherAcademicYear)->create();
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(route('evaluations.index', [
            'current_team' => $reviewer->currentTeam,
            'reporting_period' => 'invalid',
        ]))
        ->assertNotFound();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($reviewer, $otherPeriod))
        ->assertNotFound();
});

test('department users cannot open the institutional reviewer queue', function () {
    $academicYear = AcademicYear::factory()->open()->create();
    $departmentUser = User::factory()->departmentUser()->create();

    $this->actingAs($departmentUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($departmentUser))
        ->assertForbidden();
});

test('guests are redirected and super administrators can open the reviewer queue', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    ReportingPeriod::factory()->open()->for($academicYear)->create();
    $superAdmin = User::factory()->superAdmin()->create();

    $this->get(evaluationQueueRoute($superAdmin))->assertRedirect(route('login'));

    $this->actingAs($superAdmin)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($superAdmin))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('evaluations/index'));
});

test('queue orders the oldest waiting submission first', function () {
    $this->withoutVite();
    $academicYear = AcademicYear::factory()->open()->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $newer = evaluationQueueContext(
        $academicYear,
        $period,
        submittedAt: Carbon::parse('2026-09-04 12:00:00'),
    );
    $older = evaluationQueueContext(
        $academicYear,
        $period,
        submittedAt: Carbon::parse('2026-09-04 08:00:00'),
    );
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->get(evaluationQueueRoute($reviewer, $period))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 2)
            ->where('queue.0.id', $older['accomplishment']->id)
            ->where('queue.1.id', $newer['accomplishment']->id));
});

test('ready records without immutable submission history are not placed in the queue', function () {
    $this->withoutVite();
    $context = evaluationQueueContext();
    $context['submission']->delete();
    $reviewer = User::factory()->executiveOver(...Department::query()->get()->all())->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(evaluationQueueRoute($reviewer, $context['period']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('queue', 0));
});
