<?php

use App\Enums\AcademicYearStatus;
use App\Enums\ReportingPeriodStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\ReportingPeriod;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('rerunning foundation seeders preserves administrator-edited mutable fields', function () {
    $this->seed(DatabaseSeeder::class);

    $department = Department::query()->where('code', 'PLAN')->firstOrFail();
    $department->update([
        'description' => 'Administrator-maintained description',
        'is_active' => false,
    ]);

    $academicYear = AcademicYear::query()
        ->where('start_year', 2025)
        ->where('end_year', 2026)
        ->firstOrFail();
    $academicYear->update([
        'name' => 'Institutional AY 2025-2026',
        'starts_on' => '2025-07-01',
        'status' => AcademicYearStatus::Closed,
        'is_current' => false,
    ]);

    $reportingPeriod = ReportingPeriod::query()
        ->whereBelongsTo($academicYear)
        ->where('code', 'first_semester')
        ->firstOrFail();
    $reportingPeriod->update([
        'name' => 'Administrator First Term',
        'starts_on' => '2025-07-15',
        'status' => ReportingPeriodStatus::Open,
    ]);

    $demoUser = User::query()->where('email', 'superadmin@example.com')->firstOrFail();
    $demoUser->update([
        'name' => 'Administrator Renamed Account',
        'password' => 'administrator-password',
        'role' => UserRole::Reviewer,
        'department_id' => $department->id,
    ]);

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('departments', 6);
    $this->assertDatabaseCount('academic_years', 2);
    $this->assertDatabaseCount('reporting_periods', 4);
    $this->assertDatabaseCount('users', 3);

    expect($department->fresh())
        ->description->toBe('Administrator-maintained description')
        ->is_active->toBeFalse();
    expect($academicYear->fresh())
        ->name->toBe('Institutional AY 2025-2026')
        ->starts_on->toDateString()->toBe('2025-07-01')
        ->status->toBe(AcademicYearStatus::Closed)
        ->is_current->toBeFalse();
    expect($reportingPeriod->fresh())
        ->name->toBe('Administrator First Term')
        ->starts_on->toDateString()->toBe('2025-07-15')
        ->status->toBe(ReportingPeriodStatus::Open);
    expect($demoUser->fresh())
        ->name->toBe('Administrator Renamed Account')
        ->role->toBe(UserRole::Reviewer)
        ->department_id->toBe($department->id);
    expect(Hash::check('administrator-password', $demoUser->fresh()->password))->toBeTrue();
});
