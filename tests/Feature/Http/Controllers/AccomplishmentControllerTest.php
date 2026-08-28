<?php

use App\Enums\AccomplishmentStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;

function accomplishmentContext(): array
{
    $academicYear = AcademicYear::factory()->open()->create();
    $department = Department::factory()->create();
    $user = User::factory()->departmentUser()->forDepartment($department)->create();
    $period = ReportingPeriod::factory()->open()->firstSemester()->for($academicYear)->create();
    $plan = OperationalPlan::factory()->approved()->for($academicYear, 'academicYear')->for($department)->create();
    $keyResultArea = KeyResultArea::factory()->for($plan)->create();
    $planItem = PlanItem::factory()->for($keyResultArea)->create([
        'target_value' => 20,
        'target_unit' => 'staff',
        'target_operator' => 'at_least',
    ]);

    return compact('academicYear', 'department', 'user', 'period', 'plan', 'planItem');
}

test('department users create a semester accomplishment draft with a calculated percentage', function () {
    extract(accomplishmentContext());

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
        ]), [
            'reported_value' => '15',
            'accomplishment_text' => 'Fifteen staff completed training.',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $accomplishment = Accomplishment::query()->sole();
    expect($accomplishment)
        ->plan_item_id->toBe($planItem->id)
        ->reporting_period_id->toBe($period->id)
        ->percentage_accomplished->toBe('75.0000')
        ->status->toBe(AccomplishmentStatus::Draft)
        ->submitted_by->toBeNull();
});

test('qualitative accomplishments do not receive a forced percentage', function () {
    extract(accomplishmentContext());
    $planItem->update(['target_value' => null, 'target_operator' => 'qualitative']);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
        ]), ['accomplishment_text' => 'The institutional guidelines were approved.']);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect(Accomplishment::query()->sole()->percentage_accomplished)->toBeNull();
});

test('department users update only draft accomplishments in their own department', function () {
    extract(accomplishmentContext());
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create();

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('monitoring.accomplishments.update', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]), [
            'reported_value' => '20',
            'accomplishment_text' => 'All staff completed training.',
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($accomplishment->fresh())
        ->reported_value->toBe('20.0000')
        ->percentage_accomplished->toBe('100.0000')
        ->accomplishment_text->toBe('All staff completed training.');
});

test('cross-department accomplishment mutations return 404 without changing the record', function () {
    extract(accomplishmentContext());
    $accomplishment = Accomplishment::factory()->for($planItem)->for($period)->create([
        'accomplishment_text' => 'Original report',
    ]);
    $otherUser = User::factory()->departmentUser()->create();

    $response = $this->actingAs($otherUser)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->patch(route('monitoring.accomplishments.update', [
            'current_team' => $otherUser->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
            'accomplishment' => $accomplishment,
        ]), ['accomplishment_text' => 'Tampered report']);

    $response->assertNotFound();
    expect($accomplishment->fresh()->accomplishment_text)->toBe('Original report');
});

test('closed academic years forbid accomplishment creation', function () {
    extract(accomplishmentContext());
    $academicYear->update(['status' => 'closed']);

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
        ]), ['accomplishment_text' => 'Should not save']);

    $response->assertForbidden();
    $this->assertDatabaseEmpty('accomplishments');
});

test('accomplishment input requires a reported value or description', function () {
    extract(accomplishmentContext());

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
        ]));

    $response->assertInvalid(['reported_value', 'accomplishment_text']);
    $this->assertDatabaseEmpty('accomplishments');
});

test('accomplishment input rejects client controlled scope and workflow fields', function () {
    extract(accomplishmentContext());

    $response = $this->actingAs($user)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('monitoring.accomplishments.store', [
            'current_team' => $user->currentTeam,
            'reporting_period' => $period,
            'plan_item' => $planItem,
        ]), [
            'accomplishment_text' => 'Valid description',
            'status' => AccomplishmentStatus::Accepted->value,
            'percentage_accomplished' => 999,
            'submitted_by' => $user->id,
        ]);

    $response->assertInvalid(['status', 'percentage_accomplished', 'submitted_by']);
    $this->assertDatabaseEmpty('accomplishments');
});
