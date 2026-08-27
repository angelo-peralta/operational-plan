<?php

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can select a historical academic year and keep it while navigating', function () {
    AcademicYear::factory()->current()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $historicalAcademicYear = AcademicYear::factory()->closed()->create([
        'name' => 'AY 2024-2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);
    $departmentUser = User::factory()->departmentUser()->create();

    $response = $this
        ->actingAs($departmentUser)
        ->put(route('academic-years.select', [
            'current_team' => $departmentUser->currentTeam,
            'academic_year' => $historicalAcademicYear,
        ]));

    $response
        ->assertRedirect()
        ->assertSessionHas(ResolveAcademicYearContext::SESSION_KEY, $historicalAcademicYear->id);

    $this->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('selectedAcademicYear.id', $historicalAcademicYear->id)
            ->where('selectedAcademicYear.name', 'AY 2024-2025'),
        );
});

test('academic year context defaults to the current year and shares authorized user context', function () {
    $historicalAcademicYear = AcademicYear::factory()->closed()->create([
        'name' => 'AY 2024-2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);
    $currentAcademicYear = AcademicYear::factory()->current()->create([
        'name' => 'AY 2025-2026',
        'start_year' => 2025,
        'end_year' => 2026,
    ]);
    $departmentUser = User::factory()->departmentUser()->create([
        'name' => 'Department Planner',
        'email' => 'planner@example.com',
    ]);

    $response = $this
        ->actingAs($departmentUser)
        ->get(route('dashboard', ['current_team' => $departmentUser->currentTeam]));

    $response
        ->assertOk()
        ->assertSessionHas(ResolveAcademicYearContext::SESSION_KEY, $currentAcademicYear->id)
        ->assertInertia(fn (Assert $page) => $page
            ->component('dashboard')
            ->where('selectedAcademicYear.id', $currentAcademicYear->id)
            ->where('academicYears.0.id', $currentAcademicYear->id)
            ->where('academicYears.1.id', $historicalAcademicYear->id)
            ->where('auth.user.role', 'department_user')
            ->where('auth.user.department.id', $departmentUser->department_id)
            ->missing('auth.user.password'),
        );
});

test('academic year context falls back to the latest year when there is no current year', function () {
    AcademicYear::factory()->closed()->create([
        'name' => 'AY 2024-2025',
        'start_year' => 2024,
        'end_year' => 2025,
    ]);
    $latestAcademicYear = AcademicYear::factory()->create([
        'name' => 'AY 2026-2027',
        'start_year' => 2026,
        'end_year' => 2027,
    ]);
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => 999999])
        ->get(route('dashboard', ['current_team' => $reviewer->currentTeam]));

    $response
        ->assertOk()
        ->assertSessionHas(ResolveAcademicYearContext::SESSION_KEY, $latestAcademicYear->id)
        ->assertInertia(fn (Assert $page) => $page
            ->where('selectedAcademicYear.id', $latestAcademicYear->id),
        );
});
