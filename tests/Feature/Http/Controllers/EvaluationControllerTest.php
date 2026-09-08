<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
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
use Illuminate\Support\Carbon;

/** @return array<string, mixed> */
function evaluationWorkflowContext(): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $author = User::factory()->officeHead()->forDepartment($department)->create();
    $reviewer = User::factory()->executiveOver($department)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Improve completion rates.',
        'strategy' => 'Run targeted interventions.',
        'kpi_target_text' => 'At least 90% completion.',
        'documentary_evidence_requirements' => ['Completion report'],
    ]);
    $accomplishment = Accomplishment::factory()
        ->readyForReview()
        ->for($planItem)
        ->for($period)
        ->create([
            'submitted_by' => $author->id,
            'submitted_at' => now()->subDay(),
            'reported_value' => 88,
            'accomplishment_text' => 'Eighty-eight percent completed.',
        ]);
    $evidence = Evidence::factory()->for($accomplishment)->for($author, 'uploader')->create();
    $submission = AccomplishmentSubmission::factory()
        ->for($accomplishment)
        ->for($author, 'submitter')
        ->create([
            'revision' => 1,
            'evidence_count' => 1,
            'snapshot' => ['evidence' => [['id' => $evidence->id]]],
        ]);

    return compact(
        'academicYear',
        'department',
        'author',
        'reviewer',
        'period',
        'plan',
        'keyResultArea',
        'planItem',
        'accomplishment',
        'evidence',
        'submission',
    );
}

/** @param array<string, mixed> $context */
function evaluationStoreRoute(array $context, User $actor, array $overrides = []): string
{
    return route('evaluations.store', array_merge([
        'current_team' => $actor->currentTeam,
        'reporting_period' => $context['period'],
        'plan_item' => $context['planItem'],
        'accomplishment' => $context['accomplishment'],
    ], $overrides));
}

test('reviewers accept an immutable submission revision with a manual official rating', function () {
    $context = evaluationWorkflowContext();
    extract($context);
    $reviewedAt = Carbon::parse('2026-09-04 15:00:00');
    $this->travelTo($reviewedAt);

    $response = $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Exceeded->value,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $evaluation = Evaluation::query()->sole();
    expect($evaluation)
        ->accomplishment_submission_id->toBe($submission->id)
        ->reviewer_id->toBe($reviewer->id)
        ->organizational_unit_id->toBe($reviewer->organizationalUnitAssignments()->sole()->organizational_unit_id)
        ->decision->toBe(EvaluationDecision::Accepted)
        ->rating->toBe(EvaluationRating::Exceeded)
        ->remarks->toBeNull()
        ->reviewed_at->toEqual($reviewedAt);
    expect($accomplishment->fresh())
        ->status->toBe(AccomplishmentStatus::Accepted)
        ->updated_by->toBe($reviewer->id);
});

test('accepted ratings zero and one require substantive reviewer remarks', function (int $rating) {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => $rating,
            'remarks' => '   ',
        ])
        ->assertSessionHasErrors('remarks');

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::UnderReview);
    $this->assertDatabaseEmpty('evaluations');
})->with([0, 1]);

test('accepted ratings two and three may omit remarks', function (int $rating) {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => $rating,
        ])
        ->assertSessionHasNoErrors();

    expect(Evaluation::query()->sole()->rating?->value)->toBe($rating);
})->with([2, 3]);

test('requesting additional evidence requires remarks and stores no rating', function () {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::RequestAdditionalEvidence->value,
        ])
        ->assertSessionHasErrors('remarks');

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::RequestAdditionalEvidence->value,
            'rating' => 2,
            'remarks' => 'Upload the signed completion report.',
        ])
        ->assertSessionHasErrors('rating');

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::RequestAdditionalEvidence->value,
            'remarks' => '  Upload the signed completion report.  ',
        ])
        ->assertSessionHasNoErrors();

    $evaluation = Evaluation::query()->sole();
    expect($evaluation)
        ->decision->toBe(EvaluationDecision::RequestAdditionalEvidence)
        ->rating->toBeNull()
        ->remarks->toBe('Upload the signed completion report.');
    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Returned);
});

test('rejection requires remarks and permits an optional in-range rating', function (?int $rating) {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Rejected->value,
            'rating' => $rating,
            'remarks' => 'The submission does not support the reported accomplishment.',
        ])
        ->assertSessionHasNoErrors();

    expect(Evaluation::query()->sole()->rating?->value)->toBe($rating);
    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Rejected);
})->with([
    'without rating' => null,
    'with rating' => 0,
]);

test('evaluation validates decisions and official rating boundaries', function (array $payload, string $field) {
    $context = evaluationWorkflowContext();
    $reviewer = $context['reviewer'];
    $academicYear = $context['academicYear'];

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), $payload)
        ->assertSessionHasErrors($field);

    $this->assertDatabaseEmpty('evaluations');
})->with([
    'unknown decision' => [['decision' => 'approve', 'rating' => 3], 'decision'],
    'accepted without rating' => [['decision' => 'accepted'], 'rating'],
    'negative rating' => [['decision' => 'accepted', 'rating' => -1, 'remarks' => 'Invalid.'], 'rating'],
    'rating above scale' => [['decision' => 'accepted', 'rating' => 4], 'rating'],
    'decimal rating' => [['decision' => 'accepted', 'rating' => 2.5], 'rating'],
    'non numeric rating' => [['decision' => 'accepted', 'rating' => 'high'], 'rating'],
]);

test('guests and department users cannot evaluate accomplishments', function () {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->post(evaluationStoreRoute($context, $reviewer), [
        'decision' => EvaluationDecision::Accepted->value,
        'rating' => 3,
    ])->assertRedirect(route('login'));

    $this->actingAs($author)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $author), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => 3,
        ])
        ->assertForbidden();

    $this->assertDatabaseEmpty('evaluations');
});

test('evaluation rejects selected year and nested resource mismatches without disclosing records', function () {
    $context = evaluationWorkflowContext();
    extract($context);
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $otherPeriod = ReportingPeriod::factory()->open()->secondSemester()->for($academicYear)->create();
    $otherPlanItem = PlanItem::factory()->for($keyResultArea)->create();
    $otherAccomplishment = Accomplishment::factory()->readyForReview()->for($otherPlanItem)->for($period)->create();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $otherAcademicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => 3,
        ])
        ->assertNotFound();

    foreach ([
        ['reporting_period' => $otherPeriod],
        ['plan_item' => $otherPlanItem],
        ['accomplishment' => $otherAccomplishment],
    ] as $overrides) {
        $this->actingAs($reviewer)
            ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
            ->post(evaluationStoreRoute($context, $reviewer, $overrides), [
                'decision' => EvaluationDecision::Accepted->value,
                'rating' => 3,
            ])
            ->assertNotFound();
    }

    $this->assertDatabaseEmpty('evaluations');
});

test('an accomplishment without immutable submission history fails safely', function () {
    $context = evaluationWorkflowContext();
    extract($context);
    $submission->delete();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => 3,
        ])
        ->assertSessionHasErrors('accomplishment');

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::UnderReview);
    $this->assertDatabaseEmpty('evaluations');
});

test('stale duplicate evaluations cannot overwrite or append to a reviewed revision', function () {
    $context = evaluationWorkflowContext();
    extract($context);
    $url = evaluationStoreRoute($context, $reviewer);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post($url, [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => 2,
        ])
        ->assertSessionHasNoErrors();

    $firstEvaluation = Evaluation::query()->sole()->replicate();

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post($url, [
            'decision' => EvaluationDecision::Rejected->value,
            'remarks' => 'Stale decision.',
        ])
        ->assertForbidden();

    expect(Evaluation::query()->count())->toBe(1);
    expect(Evaluation::query()->sole())
        ->decision->toBe($firstEvaluation->decision)
        ->rating->toBe($firstEvaluation->rating);
});

test('client controlled scope workflow and audit fields are prohibited', function () {
    $context = evaluationWorkflowContext();
    extract($context);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => 3,
            'accomplishment_id' => $accomplishment->id,
            'accomplishment_submission_id' => $submission->id,
            'reviewer_id' => $author->id,
            'organizational_unit_id' => $department->id,
            'overrides_evaluation_id' => $submission->id,
            'reviewed_at' => now()->subYear()->toISOString(),
            'status' => AccomplishmentStatus::Accepted->value,
            'academic_year_id' => $academicYear->id,
            'reporting_period_id' => $period->id,
            'plan_item_id' => $planItem->id,
            'snapshot' => ['forged' => true],
        ])
        ->assertInvalid([
            'accomplishment_id',
            'accomplishment_submission_id',
            'reviewer_id',
            'organizational_unit_id',
            'overrides_evaluation_id',
            'reviewed_at',
            'status',
            'academic_year_id',
            'reporting_period_id',
            'plan_item_id',
            'snapshot',
        ]);

    $this->assertDatabaseEmpty('evaluations');
});

test('a returned revision can be resubmitted and evaluated without changing prior history', function () {
    $context = evaluationWorkflowContext();
    extract($context);
    $firstReviewedAt = Carbon::parse('2026-09-04 10:00:00');
    $this->travelTo($firstReviewedAt);

    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $reviewer), [
            'decision' => EvaluationDecision::RequestAdditionalEvidence->value,
            'remarks' => 'Add a signed completion report.',
        ])
        ->assertSessionHasNoErrors();

    $firstEvaluation = Evaluation::query()->sole();
    $secondEvidence = Evidence::factory()->for($accomplishment)->for($author, 'uploader')->create();
    $accomplishment->refresh()->update([
        'accomplishment_text' => 'Eighty-eight percent completed with a signed report.',
    ]);
    $resubmittedAt = Carbon::parse('2026-09-05 11:00:00');
    $this->travelTo($resubmittedAt);

    $this->actingAs($author)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.submit', [
            'current_team' => $author->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]))
        ->assertSessionHasNoErrors();

    $secondSubmission = AccomplishmentSubmission::query()->where('revision', 2)->sole();
    $secondReviewer = User::factory()->executive()->assignedToUnit($reviewer->organizationalUnitAssignments()->sole()->organizationalUnit)->create();
    $secondReviewedAt = Carbon::parse('2026-09-06 13:00:00');
    $this->travelTo($secondReviewedAt);

    $this->actingAs($secondReviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(evaluationStoreRoute($context, $secondReviewer), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Met->value,
        ])
        ->assertSessionHasNoErrors();

    expect(Evaluation::query()->count())->toBe(2);
    expect($firstEvaluation->fresh())
        ->accomplishment_submission_id->toBe($submission->id)
        ->reviewer_id->toBe($reviewer->id)
        ->decision->toBe(EvaluationDecision::RequestAdditionalEvidence)
        ->remarks->toBe('Add a signed completion report.')
        ->reviewed_at->toEqual($firstReviewedAt);
    expect($secondSubmission->evaluations()->sole())
        ->reviewer_id->toBe($secondReviewer->id)
        ->decision->toBe(EvaluationDecision::Accepted)
        ->rating->toBe(EvaluationRating::Met)
        ->reviewed_at->toEqual($secondReviewedAt);
    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Accepted);
    expect(Evidence::query()->whereKey($evidence->id)->exists())->toBeTrue();
    expect(Evidence::query()->whereKey($secondEvidence->id)->exists())->toBeTrue();
});
