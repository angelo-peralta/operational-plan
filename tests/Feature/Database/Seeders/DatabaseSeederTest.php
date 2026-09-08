<?php

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AcademicYearStatus;
use App\Enums\ReportingPeriodStatus;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultAreaTemplate;
use App\Models\OrganizationalUnit;
use App\Models\ReportingPeriod;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;

test('rerunning foundation seeders preserves administrator-edited mutable fields', function () {
    $this->seed(DatabaseSeeder::class);

    $department = Department::query()->where('code', 'CPRINT')->firstOrFail();
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
    $reviewerRole = Role::systemFor(UserRole::Executive);
    $demoUser->update([
        'name' => 'Administrator Renamed Account',
        'password' => 'administrator-password',
        'role_id' => $reviewerRole->id,
        'role' => UserRole::Executive,
        'department_id' => $department->id,
    ]);

    $keyResultAreaTemplate = KeyResultAreaTemplate::query()->where('code', 'KRA 1')->firstOrFail();
    $keyResultAreaTemplate->update(['name' => 'Administrator-maintained KRA name']);

    $this->seed(DatabaseSeeder::class);

    $this->assertDatabaseCount('organizational_units', 16);
    $this->assertDatabaseCount('academic_years', 2);
    $this->assertDatabaseCount('reporting_periods', 4);
    $this->assertDatabaseCount('roles', 7);
    $this->assertDatabaseCount('users', 7);
    $this->assertDatabaseCount('key_result_area_templates', 1);

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
        ->role_id->toBe($reviewerRole->id)
        ->role->toBe(UserRole::Executive)
        ->department_id->toBe($department->id);
    expect(Hash::check('administrator-password', $demoUser->fresh()->password))->toBeTrue();
    expect($keyResultAreaTemplate->fresh()->name)->toBe('Administrator-maintained KRA name');
});

test('seeds VP Administration with university-wide organizational visibility', function () {
    $this->seed(DatabaseSeeder::class);

    $vpAdministration = User::query()->where('email', 'vp.admin@example.com')->firstOrFail();
    $visibleUnitCodes = OrganizationalUnit::query()
        ->whereKey(app(ResolveOrganizationalScope::class)->visibleUnitIds($vpAdministration))
        ->pluck('code')
        ->all();

    expect($visibleUnitCodes)
        ->toEqualCanonicalizing([
            'SPUP', 'ACADEMIC', 'ADMIN', 'PRESIDENT', 'CPRINT', 'ICT', 'FIN', 'QA', 'HR',
            'GS', 'MED', 'SASTE', 'SBAHM', 'SITE', 'SNAHS', 'BEU',
        ]);
});
