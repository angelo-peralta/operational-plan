<?php

use App\Actions\Accomplishments\RecordAccomplishmentSubmission;
use App\Actions\Accomplishments\ResolveEffectiveEvaluation;
use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use App\Enums\OrganizationalUnitType;
use App\Enums\RemarkField;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

/** @return array<string, mixed> */
function revisedExecutiveEvaluationContext(bool $ownedByVicePresidentialArea = false): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $reportingPeriod = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $university = OrganizationalUnit::factory()->university()->create([
        'name' => 'St. Paul University Philippines',
    ]);
    $vicePresidentialArea = OrganizationalUnit::factory()->create([
        'parent_id' => $university->id,
        'name' => 'Academic Services',
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);
    $office = OrganizationalUnit::factory()->create([
        'parent_id' => $vicePresidentialArea->id,
        'name' => 'Quality Assurance Office',
        'type' => OrganizationalUnitType::Office,
    ]);
    $officeHead = User::factory()->officeHead()->assignedToUnit($office)->create([
        'name' => 'Office Head',
    ]);
    $vicePresident = User::factory()->executive()->assignedToUnit($vicePresidentialArea)->create([
        'name' => 'Vice President',
    ]);
    $president = User::factory()->executive()->assignedToUnit($university)->create([
        'name' => 'University President',
    ]);
    $planOwner = $ownedByVicePresidentialArea ? $vicePresidentialArea : $office;
    $operationalPlan = OperationalPlan::factory()
        ->approved()
        ->for($academicYear, 'academicYear')
        ->for($planOwner, 'organizationalUnit')
        ->create([
            'created_by' => $officeHead->id,
            'updated_by' => $officeHead->id,
            'submitted_by' => $officeHead->id,
            'approved_by' => $ownedByVicePresidentialArea ? $president->id : $vicePresident->id,
        ]);
    $keyResultArea = KeyResultArea::factory()->for($operationalPlan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'objective' => 'Complete the institutional quality review.',
        'kpi_target_text' => 'Complete 100% of required review activities.',
        'documentary_evidence_requirements' => ['Signed quality review report'],
    ]);
    $accomplishment = Accomplishment::factory()
        ->readyForReview()
        ->for($planItem)
        ->for($reportingPeriod)
        ->create([
            'created_by' => $officeHead->id,
            'updated_by' => $officeHead->id,
            'submitted_by' => $officeHead->id,
            'reported_value' => 100,
            'accomplishment_text' => 'All required review activities were completed.',
        ]);
    $submission = app(RecordAccomplishmentSubmission::class)->handle(
        $accomplishment,
        $officeHead,
        AccomplishmentStatus::Draft,
        AccomplishmentStatus::UnderReview,
        1,
        now(),
    );

    return compact(
        'academicYear',
        'reportingPeriod',
        'university',
        'vicePresidentialArea',
        'office',
        'officeHead',
        'vicePresident',
        'president',
        'operationalPlan',
        'keyResultArea',
        'planItem',
        'accomplishment',
        'submission',
    );
}

/** @param array<string, mixed> $context */
function revisedEvaluationStoreRoute(array $context, User $actor): string
{
    return route('evaluations.store', [
        'current_team' => $actor->currentTeam,
        'reporting_period' => $context['reportingPeriod'],
        'plan_item' => $context['planItem'],
        'accomplishment' => $context['accomplishment'],
    ]);
}

/** @param array<string, mixed> $context */
function revisedEvaluationIndexRoute(array $context, User $actor): string
{
    return route('evaluations.index', [
        'current_team' => $actor->currentTeam,
        'reporting_period' => $context['reportingPeriod'],
    ]);
}

/** @param array<string, mixed> $context */
function acceptAsVicePresident(object $test, array $context, int $rating = 2): Evaluation
{
    $test->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['vicePresident']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => $rating,
            'remarks' => $rating < 2 ? 'The evidence supports only partial accomplishment.' : 'VP review completed.',
        ])
        ->assertSessionHasNoErrors();

    return Evaluation::query()->sole();
}

test('the closest executive authority receives the initial evaluation', function () {
    $this->withoutVite();
    $context = revisedExecutiveEvaluationContext();

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['vicePresident']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.id', $context['accomplishment']->id)
            ->where('queue.0.permissions.reviewAccomplishment', true));

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['president']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('queue', 0));
});

test('an inactive closer authority does not block the next active executive', function () {
    $this->withoutVite();
    $context = revisedExecutiveEvaluationContext();
    $context['vicePresidentialArea']->update(['is_active' => false]);

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['vicePresident']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('queue', 0));

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['president']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.id', $context['accomplishment']->id)
            ->where('queue.0.permissions.reviewAccomplishment', true));

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['president']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Met->value,
        ])
        ->assertSessionHasNoErrors();

    expect(Evaluation::query()->sole()->organizational_unit_id)->toBe($context['university']->id);
});

test('the President directly evaluates work owned by a VP area', function () {
    $this->withoutVite();
    $context = revisedExecutiveEvaluationContext(ownedByVicePresidentialArea: true);

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['vicePresident']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('queue', 0));

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['president']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.organizationalUnit.id', $context['vicePresidentialArea']->id)
            ->where('queue.0.permissions.reviewAccomplishment', true));
});

test('the President can agree with a VP evaluation and becomes the effective evaluator', function () {
    $context = revisedExecutiveEvaluationContext();
    $vicePresidentEvaluation = acceptAsVicePresident($this, $context);

    expect($vicePresidentEvaluation)
        ->organizational_unit_id->toBe($context['vicePresidentialArea']->id)
        ->is_override->toBeFalse()
        ->overrides_evaluation_id->toBeNull();

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['president']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Met->value,
            'remarks' => 'I agree with the VP evaluation.',
        ])
        ->assertSessionHasNoErrors();

    $presidentialEvaluation = Evaluation::query()
        ->where('reviewer_id', $context['president']->id)
        ->sole();

    expect($presidentialEvaluation)
        ->organizational_unit_id->toBe($context['university']->id)
        ->rating->toBe(EvaluationRating::Met)
        ->is_override->toBeFalse()
        ->overrides_evaluation_id->toBeNull();
    expect($vicePresidentEvaluation->fresh())
        ->rating->toBe(EvaluationRating::Met)
        ->remarks->toBe('VP review completed.');
    expect(Evaluation::query()->count())->toBe(2);
    expect(app(ResolveEffectiveEvaluation::class)->handle($context['accomplishment']->fresh())?->id)
        ->toBe($presidentialEvaluation->id);
});

test('a Presidential override preserves and links the VP evaluation', function () {
    $context = revisedExecutiveEvaluationContext();
    $vicePresidentEvaluation = acceptAsVicePresident($this, $context);

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['president']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Exceeded->value,
            'remarks' => 'The complete evidence supports an exceeded rating.',
            'is_override' => true,
        ])
        ->assertSessionHasNoErrors();

    $presidentialEvaluation = Evaluation::query()
        ->where('reviewer_id', $context['president']->id)
        ->sole();

    expect($presidentialEvaluation)
        ->rating->toBe(EvaluationRating::Exceeded)
        ->is_override->toBeTrue()
        ->overrides_evaluation_id->toBe($vicePresidentEvaluation->id);
    expect($vicePresidentEvaluation->fresh())
        ->rating->toBe(EvaluationRating::Met)
        ->is_override->toBeFalse();
    expect(app(ResolveEffectiveEvaluation::class)->handle($context['accomplishment']->fresh())?->id)
        ->toBe($presidentialEvaluation->id);
});

test('Presidential override rules are enforced on the server', function () {
    $context = revisedExecutiveEvaluationContext();

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['vicePresident']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Exceeded->value,
            'remarks' => 'Attempted initial override.',
            'is_override' => true,
        ])
        ->assertSessionHasErrors('is_override');

    acceptAsVicePresident($this, $context);

    $staff = User::factory()->staff()->assignedToUnit($context['office'])->create();
    $this->actingAs($staff)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $staff), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Exceeded->value,
            'remarks' => 'Unauthorized override.',
            'is_override' => true,
        ])
        ->assertForbidden();

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['president']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Exceeded->value,
            'remarks' => 'A changed rating must be marked as an override.',
        ])
        ->assertSessionHasErrors('rating');

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['president']), [
            'decision' => EvaluationDecision::Accepted->value,
            'rating' => EvaluationRating::Met->value,
            'remarks' => 'This is not a rating change.',
            'is_override' => true,
        ])
        ->assertSessionHasErrors('rating');

    expect(Evaluation::query()->count())->toBe(1);
});

test('the Presidential queue exposes the VP evaluation effective rating and full history', function () {
    $this->withoutVite();
    $context = revisedExecutiveEvaluationContext();
    $vicePresidentEvaluation = acceptAsVicePresident($this, $context);

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(revisedEvaluationIndexRoute($context, $context['president']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('queue', 1)
            ->where('queue.0.presidentialReview.sourceEvaluationId', $vicePresidentEvaluation->id)
            ->where('queue.0.presidentialReview.rating', EvaluationRating::Met->value)
            ->where('queue.0.presidentialReview.authority.label', 'Vice President')
            ->where('queue.0.effectiveEvaluation.id', $vicePresidentEvaluation->id)
            ->where('queue.0.effectiveEvaluation.rating', EvaluationRating::Met->value)
            ->where('queue.0.evaluationHistory.0.id', $vicePresidentEvaluation->id)
            ->where('queue.0.evaluationHistory.0.isEffective', true)
            ->where('queue.0.permissions.reviewAccomplishment', true));
});

test('the President can attach a field-level remark to the VP evaluation', function () {
    $this->withoutVite();
    $context = revisedExecutiveEvaluationContext();
    $vicePresidentEvaluation = acceptAsVicePresident($this, $context);

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(route('operational-plans.remarks.store', [
            'current_team' => $context['president']->currentTeam,
            'operational_plan' => $context['operationalPlan'],
        ]), [
            'target_type' => 'evaluation',
            'target_id' => $vicePresidentEvaluation->id,
            'field_key' => RemarkField::Evaluation->value,
            'reporting_period_id' => $context['reportingPeriod']->id,
            'category' => 'general',
            'content' => 'Please retain this observation in the consolidated review summary.',
        ])
        ->assertSessionHasNoErrors();

    $remark = Remark::query()->sole();
    expect($remark)
        ->academic_year_id->toBe($context['academicYear']->id)
        ->operational_plan_id->toBe($context['operationalPlan']->id)
        ->remarkable_type->toBe('evaluation')
        ->remarkable_id->toBe($vicePresidentEvaluation->id)
        ->field_key->toBe(RemarkField::Evaluation)
        ->reporting_period_id->toBe($context['reportingPeriod']->id)
        ->author_id->toBe($context['president']->id);

    $this->actingAs($context['president'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->get(route('operational-plans.show', [
            'current_team' => $context['president']->currentTeam,
            'operational_plan' => $context['operationalPlan'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('review.counts.total', 1)
            ->where('review.counts.president', 1)
            ->where('review.remarks.0.targetType', 'evaluation')
            ->where('review.remarks.0.targetId', $vicePresidentEvaluation->id)
            ->where('review.remarks.0.fieldKey', RemarkField::Evaluation->value));
});

test('request revision is a distinct immutable return decision', function () {
    $context = revisedExecutiveEvaluationContext();

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['vicePresident']), [
            'decision' => EvaluationDecision::RequestRevision->value,
            'rating' => 2,
            'remarks' => 'Correct the reported total before resubmission.',
        ])
        ->assertSessionHasErrors('rating');

    $this->actingAs($context['vicePresident'])
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $context['academicYear']->id])
        ->post(revisedEvaluationStoreRoute($context, $context['vicePresident']), [
            'decision' => EvaluationDecision::RequestRevision->value,
            'remarks' => 'Correct the reported total before resubmission.',
        ])
        ->assertSessionHasNoErrors();

    expect(Evaluation::query()->sole())
        ->decision->toBe(EvaluationDecision::RequestRevision)
        ->rating->toBeNull()
        ->remarks->toBe('Correct the reported total before resubmission.');
    expect($context['accomplishment']->fresh()->status)->toBe(AccomplishmentStatus::Returned);
});
