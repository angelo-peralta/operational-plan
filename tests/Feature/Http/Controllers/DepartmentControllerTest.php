<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Route;

test('super administrators can view the department index', function () {
    $department = Department::factory()->create([
        'name' => 'Planning Office',
        'code' => 'PLAN',
    ]);
    User::factory()->departmentUser()->forDepartment($department)->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->get(route('administration.departments.index', ['current_team' => $administrator->currentTeam]));

    $response->assertRedirectToRoute('administration.organizational-units.index', ['current_team' => $administrator->currentTeam]);
});

test('reviewers cannot view the department administration index', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('administration.departments.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('legacy department creation is disabled even for administrators', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.departments.store', ['current_team' => $administrator->currentTeam]), [
            'name' => '  Planning   Office  ',
            'code' => ' plan ',
            'description' => ' Institutional planning ',
            'is_active' => true,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('organizational_units', ['code' => 'PLAN']);
});

test('department users cannot create departments', function () {
    $departmentUser = User::factory()->departmentUser()->create();

    $response = $this
        ->actingAs($departmentUser)
        ->post(route('administration.departments.store', ['current_team' => $departmentUser->currentTeam]), [
            'name' => 'Planning Office',
            'code' => 'PLAN',
            'is_active' => true,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('organizational_units', ['code' => 'PLAN']);
});

test('legacy department updates cannot bypass hierarchy validation', function () {
    $department = Department::factory()->create([
        'name' => 'Planning',
        'code' => 'PLN',
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('administration.departments.update', [
            'current_team' => $administrator->currentTeam,
            'department' => $department,
        ]), [
            'name' => 'Planning Office',
            'code' => 'PLAN',
            'description' => 'Updated description',
            'is_active' => false,
        ]);

    $response->assertForbidden();
    expect($department->fresh()->name)->toBe('Planning');
});

test('reviewers cannot update departments', function () {
    $department = Department::factory()->create([
        'name' => 'Planning',
        'code' => 'PLN',
    ]);
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->patch(route('administration.departments.update', [
            'current_team' => $reviewer->currentTeam,
            'department' => $department,
        ]), [
            'name' => 'Changed',
            'code' => 'CHG',
            'is_active' => false,
        ]);

    $response->assertForbidden();
    expect($department->fresh()->name)->toBe('Planning');
});

test('departments have no destructive delete route', function () {
    expect(Route::has('administration.departments.destroy'))->toBeFalse();
});
