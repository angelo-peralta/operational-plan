<?php

use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Department;
use App\Models\Evaluation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;

/** @return array<string, mixed> */
function evaluationPolicyContext(): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $period = ReportingPeriod::factory()->open()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create();
    $accomplishment = Accomplishment::factory()->readyForReview()->for($planItem)->for($period)->create();
    $submission = AccomplishmentSubmission::factory()->for($accomplishment)->create();

    return compact('academicYear', 'department', 'period', 'plan', 'keyResultArea', 'planItem', 'accomplishment', 'submission');
}

test('assigned executives and system administrators can open the evaluation queue', function () {
    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can('viewAny', Evaluation::class))->toBeTrue()
        ->and(User::factory()->superAdmin()->create()->can('viewAny', Evaluation::class))->toBeTrue()
        ->and(User::factory()->departmentUser()->create()->can('viewAny', Evaluation::class))->toBeFalse();
});

test('executives can evaluate while administrators have support access only', function () {
    $context = evaluationPolicyContext();
    $accomplishment = $context['accomplishment'];

    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can('create', [Evaluation::class, $accomplishment]))->toBeTrue()
        ->and(User::factory()->superAdmin()->create()->can('create', [Evaluation::class, $accomplishment]))->toBeFalse();
});

test('department users cannot evaluate even their own department submission', function () {
    $context = evaluationPolicyContext();
    $departmentUser = User::factory()
        ->departmentUser()
        ->forDepartment($context['department'])
        ->create();

    expect($departmentUser->can('create', [Evaluation::class, $context['accomplishment']]))->toBeFalse();
});

test('only ready for review accomplishments are evaluable', function (AccomplishmentStatus $status) {
    $context = evaluationPolicyContext();
    $context['accomplishment']->update(['status' => $status]);
    $reviewer = User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create();

    expect($reviewer->can('create', [Evaluation::class, $context['accomplishment']->fresh()]))->toBeFalse();
})->with(array_values(array_filter(
    AccomplishmentStatus::cases(),
    fn (AccomplishmentStatus $status): bool => $status !== AccomplishmentStatus::UnderReview,
)));

test('evaluation is forbidden in closed years draft periods and non approved plans', function (string $scope, string $status) {
    $context = evaluationPolicyContext();

    match ($scope) {
        'year' => $context['academicYear']->update(['status' => $status]),
        'period' => $context['period']->update(['status' => $status]),
        'plan' => $context['plan']->update(['status' => $status]),
    };

    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can(
        'create',
        [Evaluation::class, $context['accomplishment']->fresh()],
    ))->toBeFalse();
})->with([
    'closed academic year' => ['year', 'closed'],
    'draft reporting period' => ['period', ReportingPeriodStatus::Draft->value],
    'draft operational plan' => ['plan', OperationalPlanStatus::Draft->value],
    'submitted operational plan' => ['plan', OperationalPlanStatus::Submitted->value],
    'closed operational plan' => ['plan', OperationalPlanStatus::Closed->value],
]);

test('a closed reporting period remains reviewable while its academic year is open', function () {
    $context = evaluationPolicyContext();
    $context['period']->update(['status' => ReportingPeriodStatus::Closed]);

    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can(
        'create',
        [Evaluation::class, $context['accomplishment']->fresh()],
    ))->toBeTrue();
});

test('mismatched academic year relationships cannot be evaluated', function () {
    $context = evaluationPolicyContext();
    $otherAcademicYear = AcademicYear::factory()->open()->create();
    $context['period']->update(['academic_year_id' => $otherAcademicYear->id]);

    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can(
        'create',
        [Evaluation::class, $context['accomplishment']->fresh()],
    ))->toBeFalse();
});

test('stored evaluation history is visible only within authorized accomplishment scope', function () {
    $context = evaluationPolicyContext();
    $evaluation = Evaluation::factory()->for($context['submission'], 'submission')->create();
    $ownDepartmentUser = User::factory()
        ->departmentUser()
        ->forDepartment($context['department'])
        ->create();
    $otherDepartmentUser = User::factory()->departmentUser()->create();

    expect(User::factory()->executiveOver(...(isset($context) ? [$context['department']] : []))->create()->can('view', $evaluation))->toBeTrue()
        ->and(User::factory()->superAdmin()->create()->can('view', $evaluation))->toBeTrue()
        ->and($ownDepartmentUser->can('view', $evaluation))->toBeTrue()
        ->and($otherDepartmentUser->can('view', $evaluation))->toBeFalse();
});

test('stored evaluations cannot be changed or deleted through policy abilities', function () {
    $context = evaluationPolicyContext();
    $evaluation = Evaluation::factory()->for($context['submission'], 'submission')->create();
    $superAdmin = User::factory()->superAdmin()->create();

    expect($superAdmin->can('update', $evaluation))->toBeFalse()
        ->and($superAdmin->can('delete', $evaluation))->toBeFalse()
        ->and($superAdmin->can('restore', $evaluation))->toBeFalse()
        ->and($superAdmin->can('forceDelete', $evaluation))->toBeFalse();
});
