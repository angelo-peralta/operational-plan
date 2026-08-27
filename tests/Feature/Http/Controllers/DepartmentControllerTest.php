<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

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

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('administration/departments/index')
        ->has('departments', 1)
        ->where('departments.0.name', 'Planning Office')
        ->where('departments.0.code', 'PLAN')
        ->where('departments.0.userCount', 1),
    );
});

test('reviewers cannot view the department administration index', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('administration.departments.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('super administrators can create departments', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.departments.store', ['current_team' => $administrator->currentTeam]), [
            'name' => '  Planning   Office  ',
            'code' => ' plan ',
            'description' => ' Institutional planning ',
            'is_active' => true,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('departments', [
        'name' => 'Planning Office',
        'code' => 'PLAN',
        'description' => 'Institutional planning',
        'is_active' => true,
    ]);
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
    $this->assertDatabaseMissing('departments', ['code' => 'PLAN']);
});

test('super administrators can update departments', function () {
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

    $response->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'name' => 'Planning Office',
        'code' => 'PLAN',
        'description' => 'Updated description',
        'is_active' => false,
    ]);
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
