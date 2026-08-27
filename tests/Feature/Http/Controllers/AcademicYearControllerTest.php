<?php

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('super administrators can view the academic year index', function () {
    $academicYear = AcademicYear::factory()->current()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $academicYear->reportingPeriods()->createMany([
        ['name' => 'First Semester', 'code' => 'first_semester', 'sequence' => 1],
        ['name' => 'Second Semester', 'code' => 'second_semester', 'sequence' => 2],
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->get(route('academic-years.index', ['current_team' => $administrator->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('academic-years/index')
        ->has('academicYears', 1)
        ->where('academicYears.0.name', 'AY 2025-2026')
        ->where('academicYears.0.reportingPeriods.0.code', 'first_semester')
        ->where('academicYears.0.reportingPeriods.1.code', 'second_semester')
        ->has('academicYearStatuses', 4)
        ->has('reportingPeriodStatuses', 3),
    );
});

test('reviewers cannot view the academic year administration index', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('academic-years.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('super administrators can create a current academic year with two semesters', function () {
    $previousCurrent = AcademicYear::factory()->current()->create([
        'name' => 'AY 2024-2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('academic-years.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'starts_on' => '2025-06-01',
            'ends_on' => '2026-05-31',
            'status' => AcademicYearStatus::Open->value,
            'is_current' => true,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $academicYear = AcademicYear::query()->where('name', 'AY 2025-2026')->firstOrFail();

    expect($academicYear->is_current)->toBeTrue()
        ->and($previousCurrent->fresh()->is_current)->toBeFalse();

    $this->assertDatabaseHas('reporting_periods', [
        'academic_year_id' => $academicYear->id,
        'name' => 'First Semester',
        'code' => 'first_semester',
        'sequence' => 1,
        'status' => 'draft',
    ]);
    $this->assertDatabaseHas('reporting_periods', [
        'academic_year_id' => $academicYear->id,
        'name' => 'Second Semester',
        'code' => 'second_semester',
        'sequence' => 2,
        'status' => 'draft',
    ]);
});

test('department users cannot create academic years', function () {
    $departmentUser = User::factory()->departmentUser()->create();

    $response = $this
        ->actingAs($departmentUser)
        ->post(route('academic-years.store', ['current_team' => $departmentUser->currentTeam]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'status' => AcademicYearStatus::Open->value,
            'is_current' => true,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('academic_years', ['name' => 'AY 2025-2026']);
});

test('academic year creation rejects nonconsecutive years and a draft current year', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('academic-years.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'AY 2025-2027',
            'start_year' => 2025,
            'end_year' => 2027,
            'status' => AcademicYearStatus::Draft->value,
            'is_current' => true,
        ]);

    $response->assertSessionHasErrors([
        'end_year' => 'The end year must be one year after the start year.',
        'is_current' => 'Only an open academic year may be current.',
    ]);
    $this->assertDatabaseMissing('academic_years', ['name' => 'AY 2025-2027']);
});

test('academic year dates must fall in their declared boundary years', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('academic-years.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'starts_on' => '2024-12-31',
            'ends_on' => '2027-01-01',
            'status' => AcademicYearStatus::Open->value,
            'is_current' => false,
        ]);

    $response->assertSessionHasErrors(['starts_on', 'ends_on']);
    $this->assertDatabaseMissing('academic_years', ['name' => 'AY 2025-2026']);
});

test('super administrators can switch the current academic year', function () {
    $previousCurrent = AcademicYear::factory()->current()->create([
        'name' => 'AY 2024-2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);
    $nextAcademicYear = AcademicYear::factory()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $nextAcademicYear,
        ]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'starts_on' => '2025-06-01',
            'ends_on' => '2026-05-31',
            'status' => AcademicYearStatus::Open->value,
            'is_current' => true,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($previousCurrent->fresh()->is_current)->toBeFalse()
        ->and($nextAcademicYear->fresh()->is_current)->toBeTrue();
});

test('closing a current academic year clears its current flag', function () {
    $academicYear = AcademicYear::factory()->current()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $academicYear,
        ]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'starts_on' => '2025-06-01',
            'ends_on' => '2026-05-31',
            'status' => AcademicYearStatus::Closed->value,
            'is_current' => true,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    expect($academicYear->fresh())
        ->status->toBe(AcademicYearStatus::Closed)
        ->is_current->toBeFalse();
});

test('academic year updates cannot exclude existing reporting period dates', function () {
    $academicYear = AcademicYear::factory()->open()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'starts_on' => '2025-06-01',
        'ends_on' => '2026-05-31',
    ]);
    ReportingPeriod::factory()->for($academicYear)->firstSemester()->create([
        'starts_on' => '2025-06-15',
        'ends_on' => '2025-12-15',
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $academicYear,
        ]), [
            'name' => 'AY 2025-2026',
            'start_year' => 2025,
            'end_year' => 2026,
            'starts_on' => '2025-07-01',
            'ends_on' => '2026-05-31',
            'status' => AcademicYearStatus::Open->value,
            'is_current' => false,
        ]);

    $response->assertSessionHasErrors('starts_on');
    expect($academicYear->fresh()->starts_on?->toDateString())->toBe('2025-06-01');
});

test('reviewers cannot update academic years', function () {
    $academicYear = AcademicYear::factory()->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->patch(route('academic-years.update', [
            'current_team' => $reviewer->currentTeam,
            'academic_year' => $academicYear,
        ]), [
            'name' => $academicYear->name,
            'start_year' => $academicYear->start_year,
            'end_year' => $academicYear->end_year,
            'status' => AcademicYearStatus::Open->value,
            'is_current' => false,
        ]);

    $response->assertForbidden();
    expect($academicYear->fresh()->status)->toBe(AcademicYearStatus::Draft);
});
