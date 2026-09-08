<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Enums\ReportingPeriodStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Carbon;

/** @return array<string, mixed> */
function accomplishmentSubmissionContext(bool $withEvidence = true): array
{
    $academicYear = AcademicYear::factory()->open()->create([
        'name' => 'AY 2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
    ]);
    $department = Department::factory()->create([
        'name' => 'Planning Office',
        'code' => 'PLAN',
    ]);
    $coAccountableDepartment = Department::factory()->create([
        'name' => 'Quality Office',
        'code' => 'QA',
    ]);
    $user = User::factory()->officeHead()->forDepartment($department)->create([
        'name' => 'Department Author',
    ]);
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create([
        'name' => 'First Semester',
        'code' => 'first-semester',
        'sequence' => 1,
    ]);
    $plan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for($department)
        ->create([
            'accountable_name' => 'Planning Director',
            'accountable_position' => 'Director',
            'goal' => 'Improve institutional outcomes.',
        ]);
    $keyResultArea = KeyResultArea::factory()->for($plan)->create([
        'code' => 'KRA 1',
        'name' => 'Institutional Performance',
    ]);
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Train employees.',
        'strategy' => 'Conduct focused workshops.',
        'kpi_target_text' => 'Train at least 20 employees.',
        'target_value' => 20,
        'target_unit' => 'employees',
        'target_operator' => 'at_least',
        'target_frequency' => 'semester',
        'resources_needed' => 'Training venue',
        'documentary_evidence_requirements' => ['Attendance sheet', 'Completion report'],
        'manual_co_accountable_units' => ['Vice President for Academics'],
    ]);
    $planItem->coAccountableOrganizationalUnits()->attach($coAccountableDepartment);
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create([
        'reported_value' => 15,
        'accomplishment_text' => 'Fifteen employees completed the workshop.',
        'percentage_accomplished' => 75,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $evidence = $withEvidence
        ? Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create([
            'evidence_type' => 'Attendance sheet',
            'title' => 'Workshop attendance',
            'description' => 'Signed attendance sheet.',
            'original_filename' => 'attendance.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 2048,
            'checksum' => str_repeat('a', 64),
        ])
        : null;

    return compact(
        'academicYear',
        'department',
        'coAccountableDepartment',
        'user',
        'period',
        'plan',
        'keyResultArea',
        'planItem',
        'accomplishment',
        'evidence',
    );
}

/** @param array<string, mixed> $context */
function accomplishmentSubmissionRoute(array $context, array $overrides = []): string
{
    return route('monitoring.accomplishments.submit', array_merge([
        'current_team' => $context['user']->currentTeam,
        'reporting_period' => $context['period'],
        'plan_item' => $context['planItem'],
        'accomplishment' => $context['accomplishment'],
    ], $overrides));
}

test('office heads submit accomplishments for manual review with an immutable evidence snapshot', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);
    $submittedAt = Carbon::parse('2026-09-04 09:30:00');
    $this->travelTo($submittedAt);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $submittedAccomplishment = $accomplishment->fresh();
    expect($submittedAccomplishment)
        ->status->toBe(AccomplishmentStatus::UnderReview)
        ->submitted_by->toBe($user->id)
        ->submitted_at->toEqual($submittedAt)
        ->resubmitted_at->toBeNull();

    $submission = AccomplishmentSubmission::query()->sole();
    expect($submission)
        ->revision->toBe(1)
        ->submitted_by->toBe($user->id)
        ->from_status->value->toBe(AccomplishmentStatus::Draft->value)
        ->to_status->value->toBe(AccomplishmentStatus::UnderReview->value)
        ->evidence_count->toBe(1)
        ->submitted_at->toEqual($submittedAt);
    expect($submission->snapshot)
        ->toHaveKey('academic_year.name', 'AY 2026-2027')
        ->toHaveKey('organizational_unit.code', 'PLAN')
        ->toHaveKey('operational_plan.goal', 'Improve institutional outcomes.')
        ->toHaveKey('key_result_area.code', 'KRA 1')
        ->toHaveKey('plan_item.kpi_target_text', 'Train at least 20 employees.')
        ->toHaveKey('plan_item.manual_co_accountable_units', ['Vice President for Academics'])
        ->toHaveKey('plan_item.co_accountable_organizational_units.0.code', 'QA')
        ->toHaveKey('reporting_period.code', 'first-semester')
        ->toHaveKey('accomplishment.reported_value', '15.0000')
        ->toHaveKey('accomplishment.accomplishment_text', 'Fifteen employees completed the workshop.')
        ->toHaveKey('accomplishment.created_by.id', $user->id)
        ->toHaveKey('accomplishment.updated_by.id', $user->id)
        ->toHaveKey('evidence.0.id', $evidence->id)
        ->toHaveKey('evidence.0.original_filename', 'attendance.pdf')
        ->toHaveKey('evidence.0.checksum', str_repeat('a', 64))
        ->toHaveKey('evidence.0.uploaded_by.id', $user->id)
        ->toHaveKey('submitted_by.name', 'Department Author');
    expect(array_key_exists('stored_path', $submission->snapshot['evidence'][0]))->toBeFalse();
    expect(Evidence::query()->find($evidence->id))->not->toBeNull();
});

test('submission rejects an empty accomplishment even when it has evidence', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);
    $accomplishment->update(['reported_value' => null, 'accomplishment_text' => null]);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context));

    $response->assertSessionHasErrors('accomplishment');
    expect($accomplishment->fresh())
        ->status->toBe(AccomplishmentStatus::Draft)
        ->submitted_at->toBeNull();
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('submission requires uploaded evidence rather than documentary requirement text', function () {
    $context = accomplishmentSubmissionContext(withEvidence: false);
    extract($context);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context));

    $response->assertSessionHasErrors([
        'evidence' => 'Upload at least one evidence file before submitting.',
    ]);
    expect($planItem->documentary_evidence_requirements)->not->toBeEmpty();
    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Draft);
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('returned accomplishments require new evidence and append an immutable resubmission revision', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);
    $firstSubmittedAt = Carbon::parse('2026-09-04 09:30:00');
    $evidence->forceFill(['created_at' => $firstSubmittedAt])->save();
    $this->travelTo($firstSubmittedAt);

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertSessionHasNoErrors();

    $firstSubmission = AccomplishmentSubmission::query()->sole();
    $firstSnapshot = $firstSubmission->snapshot;
    $accomplishment->refresh()->update([
        'status' => AccomplishmentStatus::Returned,
        'accomplishment_text' => 'Fifteen employees completed the workshop with signed records.',
    ]);
    $resubmittedAt = Carbon::parse('2026-09-05 14:15:00');
    $this->travelTo($resubmittedAt);
    $newEvidence = Evidence::factory()->for($accomplishment)->for($user, 'uploader')->create([
        'original_filename' => 'completion-report.pdf',
        'checksum' => str_repeat('b', 64),
    ]);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context));

    $response->assertSessionHasNoErrors()->assertRedirect();
    $resubmittedAccomplishment = $accomplishment->fresh();
    expect($resubmittedAccomplishment)
        ->status->toBe(AccomplishmentStatus::UnderReview)
        ->submitted_by->toBe($user->id)
        ->submitted_at->toEqual($firstSubmittedAt)
        ->resubmitted_at->toEqual($resubmittedAt);

    $submissions = AccomplishmentSubmission::query()->orderBy('revision')->get();
    expect($submissions)->toHaveCount(2);
    expect($submissions[0])
        ->revision->toBe(1)
        ->evidence_count->toBe(1)
        ->snapshot->toBe($firstSnapshot);
    expect($submissions[1])
        ->revision->toBe(2)
        ->from_status->value->toBe(AccomplishmentStatus::Returned->value)
        ->to_status->value->toBe(AccomplishmentStatus::UnderReview->value)
        ->evidence_count->toBe(2);
    expect($submissions[1]->snapshot)
        ->toHaveKey('accomplishment.accomplishment_text', 'Fifteen employees completed the workshop with signed records.')
        ->toHaveKey('evidence.1.id', $newEvidence->id)
        ->toHaveKey('evidence.1.checksum', str_repeat('b', 64));
    expect(Evidence::query()->whereKey($evidence->id)->exists())->toBeTrue();
});

test('returned accomplishments cannot be resubmitted without additional evidence', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertSessionHasNoErrors();
    $accomplishment->refresh()->update(['status' => AccomplishmentStatus::Returned]);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context));

    $response->assertSessionHasErrors([
        'evidence' => 'Upload at least one additional evidence file before resubmitting.',
    ]);
    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Returned);
    expect(AccomplishmentSubmission::query()->count())->toBe(1);
});

test('a stale duplicate submission cannot append another revision', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertForbidden();

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::UnderReview);
    expect(AccomplishmentSubmission::query()->count())->toBe(1);
});

test('guests are redirected and reviewers cannot submit department accomplishments', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);

    $this->post(accomplishmentSubmissionRoute($context))->assertRedirect(route('login'));

    $reviewer = User::factory()->executiveOver($department)->create();
    $context['user'] = $reviewer;
    $this->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertForbidden();

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Draft);
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('cross department and cross academic year submissions return 404', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);
    $otherUser = User::factory()->officeHead()->create();
    $context['user'] = $otherUser;

    $this->actingAs($otherUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertNotFound();

    $context['user'] = $user;
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $otherAcademicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertNotFound();

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Draft);
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('nested reporting period plan item and accomplishment mismatches return 404', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);
    $otherPeriod = ReportingPeriod::factory()->open()->secondSemester()->for($academicYear)->create();
    $otherPlanItem = PlanItem::factory()->for($keyResultArea)->create();
    $otherAccomplishment = Accomplishment::factory()->for($otherPlanItem)->for($period)->create();

    foreach ([
        ['reporting_period' => $otherPeriod],
        ['plan_item' => $otherPlanItem],
        ['accomplishment' => $otherAccomplishment],
    ] as $overrides) {
        $this->actingAs($user)
            ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
            ->post(accomplishmentSubmissionRoute($context, $overrides))
            ->assertNotFound();
    }

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Draft);
    $this->assertDatabaseEmpty('accomplishment_submissions');
});

test('closed years periods and unapproved plans forbid submission', function (string $scope, string $status) {
    $context = accomplishmentSubmissionContext();
    extract($context);

    match ($scope) {
        'year' => $academicYear->update(['status' => $status]),
        'period' => $period->update(['status' => $status]),
        'plan' => $plan->update(['status' => $status]),
    };

    $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context))
        ->assertForbidden();

    expect($accomplishment->fresh()->status)->toBe(AccomplishmentStatus::Draft);
    $this->assertDatabaseEmpty('accomplishment_submissions');
})->with([
    'closed academic year' => ['year', 'closed'],
    'draft reporting period' => ['period', ReportingPeriodStatus::Draft->value],
    'closed reporting period' => ['period', ReportingPeriodStatus::Closed->value],
    'draft operational plan' => ['plan', OperationalPlanStatus::Draft->value],
    'closed operational plan' => ['plan', OperationalPlanStatus::Closed->value],
]);

test('submission rejects client controlled workflow and snapshot fields', function () {
    $context = accomplishmentSubmissionContext();
    extract($context);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(accomplishmentSubmissionRoute($context), [
            'status' => AccomplishmentStatus::Accepted->value,
            'created_by' => User::factory()->superAdmin()->create()->id,
            'updated_by' => User::factory()->superAdmin()->create()->id,
            'submitted_by' => User::factory()->superAdmin()->create()->id,
            'submitted_at' => now()->subYear()->toISOString(),
            'resubmitted_at' => now()->subYear()->toISOString(),
            'revision' => 99,
            'snapshot' => ['forged' => true],
        ]);

    $response->assertInvalid([
        'status',
        'created_by',
        'updated_by',
        'submitted_by',
        'submitted_at',
        'resubmitted_at',
        'revision',
        'snapshot',
    ]);
    expect($accomplishment->fresh())
        ->status->toBe(AccomplishmentStatus::Draft)
        ->submitted_by->toBeNull();
    $this->assertDatabaseEmpty('accomplishment_submissions');
});
