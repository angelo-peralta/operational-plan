<?php

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('super administrators can view the user index with active departments', function () {
    $activeDepartment = Department::factory()->create([
        'name' => 'Planning Office',
        'code' => 'PLAN',
    ]);
    Department::factory()->inactive()->create([
        'name' => 'Inactive Office',
        'code' => 'OLD',
    ]);
    $managedUser = User::factory()->departmentUser()->forDepartment($activeDepartment)->create([
        'name' => 'Department Planner',
    ]);
    $administrator = User::factory()->superAdmin()->create([
        'name' => 'System Administrator',
    ]);

    $response = $this
        ->actingAs($administrator)
        ->get(route('administration.users.index', ['current_team' => $administrator->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('administration/users/index')
        ->has('users', 2)
        ->where('users.0.name', 'Department Planner')
        ->where('users.0.department.id', $managedUser->department_id)
        ->where('users.0.role', UserRole::DepartmentUser->value)
        ->has('departments', 1)
        ->where('departments.0.id', $activeDepartment->id)
        ->has('roles', 3),
    );
});

test('reviewers cannot view the user administration index', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('administration.users.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('super administrators can create department users with a personal team', function () {
    $department = Department::factory()->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => '  New   Planner ',
            'email' => ' NEW.PLANNER@EXAMPLE.COM ',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::DepartmentUser->value,
            'department_id' => $department->id,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $user = User::query()->where('email', 'new.planner@example.com')->firstOrFail();
    $personalTeam = $user->personalTeam();

    expect($user->name)->toBe('New Planner')
        ->and($user->role)->toBe(UserRole::DepartmentUser)
        ->and($user->department_id)->toBe($department->id)
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($personalTeam)->not->toBeNull()
        ->and($user->current_team_id)->toBe($personalTeam?->id)
        ->and($user->teamRole($personalTeam))->toBe(TeamRole::Owner);
});

test('department users require a department when created', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'New Planner',
            'email' => 'new.planner@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::DepartmentUser->value,
            'department_id' => null,
        ]);

    $response->assertSessionHasErrors('department_id');
    $this->assertDatabaseMissing('users', ['email' => 'new.planner@example.com']);
});

test('department users cannot be assigned to an inactive department', function () {
    $inactiveDepartment = Department::factory()->inactive()->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'New Planner',
            'email' => 'new.planner@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::DepartmentUser->value,
            'department_id' => $inactiveDepartment->id,
        ]);

    $response->assertSessionHasErrors('department_id');
    $this->assertDatabaseMissing('users', ['email' => 'new.planner@example.com']);
});

test('department users cannot create users', function () {
    $departmentUser = User::factory()->departmentUser()->create();

    $response = $this
        ->actingAs($departmentUser)
        ->post(route('administration.users.store', ['current_team' => $departmentUser->currentTeam]), [
            'name' => 'New Reviewer',
            'email' => 'new.reviewer@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::Reviewer->value,
            'department_id' => null,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'new.reviewer@example.com']);
});

test('super administrators can update users without replacing a blank password', function () {
    $department = Department::factory()->create();
    $managedUser = User::factory()->departmentUser()->forDepartment($department)->create([
        'name' => 'Department Planner',
        'email' => 'planner@example.com',
        'password' => 'original-password',
    ]);
    $originalPasswordHash = $managedUser->password;
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->patch(route('administration.users.update', [
            'current_team' => $administrator->currentTeam,
            'user' => $managedUser,
        ]), [
            'name' => 'Updated Reviewer',
            'email' => 'updated.reviewer@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role' => UserRole::Reviewer->value,
            'department_id' => null,
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $managedUser->refresh();

    expect($managedUser->name)->toBe('Updated Reviewer')
        ->and($managedUser->email)->toBe('updated.reviewer@example.com')
        ->and($managedUser->role)->toBe(UserRole::Reviewer)
        ->and($managedUser->department_id)->toBeNull()
        ->and($managedUser->email_verified_at)->toBeNull()
        ->and($managedUser->password)->toBe($originalPasswordHash);
});

test('reviewers cannot update users', function () {
    $managedUser = User::factory()->departmentUser()->create([
        'name' => 'Department Planner',
    ]);
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->patch(route('administration.users.update', [
            'current_team' => $reviewer->currentTeam,
            'user' => $managedUser,
        ]), [
            'name' => 'Changed Name',
            'email' => $managedUser->email,
            'role' => UserRole::Reviewer->value,
            'department_id' => null,
        ]);

    $response->assertForbidden();
    expect($managedUser->fresh()->name)->toBe('Department Planner');
});

test('users have no destructive delete route', function () {
    expect(Route::has('administration.users.destroy'))->toBeFalse();
});
