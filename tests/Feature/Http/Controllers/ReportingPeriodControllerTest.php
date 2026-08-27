<?php

use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use App\Models\User;

test('super administrators can update a reporting period', function () {
    $academicYear = AcademicYear::factory()->open()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'starts_on' => '2025-06-01',
        'ends_on' => '2026-05-31',
    ]);
    $reportingPeriod = ReportingPeriod::factory()->for($academicYear)->firstSemester()->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.reporting-periods.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $academicYear,
            'reporting_period' => $reportingPeriod,
        ]), [
            'name' => 'Semester One',
            'code' => 'Semester One',
            'sequence' => 1,
            'starts_on' => '2025-06-01',
            'ends_on' => '2025-12-15',
            'status' => ReportingPeriodStatus::Open->value,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('reporting_periods', [
        'id' => $reportingPeriod->id,
        'name' => 'Semester One',
        'code' => 'semester_one',
        'sequence' => 1,
        'status' => 'open',
    ]);
});

test('reviewers cannot update reporting periods', function () {
    $academicYear = AcademicYear::factory()->create();
    $reportingPeriod = ReportingPeriod::factory()->for($academicYear)->firstSemester()->create();
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->patch(route('academic-years.reporting-periods.update', [
            'current_team' => $reviewer->currentTeam,
            'academic_year' => $academicYear,
            'reporting_period' => $reportingPeriod,
        ]), [
            'name' => 'Changed Semester',
            'code' => 'changed_semester',
            'sequence' => 1,
            'status' => ReportingPeriodStatus::Open->value,
        ]);

    $response->assertForbidden();
    expect($reportingPeriod->fresh()->name)->toBe('First Semester');
});

test('reporting period dates must stay inside the parent academic year boundaries', function () {
    $academicYear = AcademicYear::factory()->open()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
        'starts_on' => '2025-06-01',
        'ends_on' => '2026-05-31',
    ]);
    $reportingPeriod = ReportingPeriod::factory()->for($academicYear)->firstSemester()->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.reporting-periods.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $academicYear,
            'reporting_period' => $reportingPeriod,
        ]), [
            'name' => 'First Semester',
            'code' => 'first_semester',
            'sequence' => 1,
            'starts_on' => '2025-05-31',
            'ends_on' => '2026-06-01',
            'status' => ReportingPeriodStatus::Open->value,
        ]);

    $response->assertSessionHasErrors(['starts_on', 'ends_on']);
    expect($reportingPeriod->fresh()->starts_on)->toBeNull()
        ->and($reportingPeriod->fresh()->ends_on)->toBeNull();
});

test('reporting period updates return 404 for a period from another academic year', function () {
    $academicYear = AcademicYear::factory()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $otherAcademicYear = AcademicYear::factory()->create([
        'name' => 'AY 2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
    ]);
    $otherReportingPeriod = ReportingPeriod::factory()->for($otherAcademicYear)->firstSemester()->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('academic-years.reporting-periods.update', [
            'current_team' => $administrator->currentTeam,
            'academic_year' => $academicYear,
            'reporting_period' => $otherReportingPeriod,
        ]), [
            'name' => 'Changed Semester',
            'code' => 'changed_semester',
            'sequence' => 1,
            'status' => ReportingPeriodStatus::Open->value,
        ]);

    $response->assertNotFound();
    expect($otherReportingPeriod->fresh()->name)->toBe('First Semester');
});
