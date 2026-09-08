<?php

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\Role;
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
    $departmentUserRole = Role::systemFor(UserRole::Staff);
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
        ->where('users.0.assignments.0.organizational_unit_id', $activeDepartment->id)
        ->where('users.0.role', UserRole::Staff->value)
        ->where('users.0.roleLabel', 'Secretary / Staff')
        ->has('organizationalUnits', 2)
        ->has('roles', 4)
        ->where('roles', UserRole::options()),
    );
});

test('reviewers cannot view the user administration index', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('administration.users.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('system administrators can create assigned staff with a personal team', function () {
    $department = Department::factory()->create();
    $departmentUserRole = Role::systemFor(UserRole::Staff);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => '  New   Planner ',
            'email' => ' NEW.PLANNER@EXAMPLE.COM ',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::Staff->value,
            'assignments' => [[
                'organizational_unit_id' => $department->id, 'assignment_type' => 'staff',
                'is_primary' => true, 'is_active' => true,
            ]],
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $user = User::query()->where('email', 'new.planner@example.com')->firstOrFail();
    $personalTeam = $user->personalTeam();

    expect($user->name)->toBe('New Planner')
        ->and($user->role_id)->toBe($departmentUserRole->id)
        ->and($user->role)->toBe(UserRole::Staff)
        ->and($user->department_id)->toBeNull()
        ->and($user->organizationalUnitAssignments()->sole()->organizational_unit_id)->toBe($department->id)
        ->and(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($personalTeam)->not->toBeNull()
        ->and($user->current_team_id)->toBe($personalTeam?->id)
        ->and($user->teamRole($personalTeam))->toBe(TeamRole::Owner);
});

test('user creation requires an explicit assignments collection', function () {
    $departmentUserRole = Role::systemFor(UserRole::Staff);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'New Planner',
            'email' => 'new.planner@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::Staff->value,
        ]);

    $response->assertSessionHasErrors('assignments');
    $this->assertDatabaseMissing('users', ['email' => 'new.planner@example.com']);
});

test('assignments to inactive units confer no operational authority', function () {
    $inactiveDepartment = Department::factory()->inactive()->create();
    $departmentUserRole = Role::systemFor(UserRole::Staff);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'New Planner',
            'email' => 'new.planner@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::Staff->value,
            'assignments' => [[
                'organizational_unit_id' => $inactiveDepartment->id, 'assignment_type' => 'staff',
                'is_primary' => true, 'is_active' => true,
            ]],
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();
    $user = User::query()->where('email', 'new.planner@example.com')->firstOrFail();
    $year = AcademicYear::factory()->open()->create();
    expect($user->can('create', [OperationalPlan::class, $year]))->toBeFalse();
});

test('department users cannot create users', function () {
    $departmentUser = User::factory()->departmentUser()->create();
    $reviewerRole = Role::systemFor(UserRole::Executive);

    $response = $this
        ->actingAs($departmentUser)
        ->post(route('administration.users.store', ['current_team' => $departmentUser->currentTeam]), [
            'name' => 'New Reviewer',
            'email' => 'new.reviewer@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role' => UserRole::Executive->value,
            'assignments' => [],
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('users', ['email' => 'new.reviewer@example.com']);
});

test('super administrators can update users without replacing a blank password', function () {
    $department = Department::factory()->create();
    $reviewerRole = Role::systemFor(UserRole::Executive);
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
            'role' => UserRole::Executive->value,
            'assignments' => [],
        ]);

    $response->assertSessionHasNoErrors()->assertRedirect();

    $managedUser->refresh();

    expect($managedUser->name)->toBe('Updated Reviewer')
        ->and($managedUser->email)->toBe('updated.reviewer@example.com')
        ->and($managedUser->role_id)->toBe($reviewerRole->id)
        ->and($managedUser->role)->toBe(UserRole::Executive)
        ->and($managedUser->department_id)->toBe($department->id)
        ->and($managedUser->organizationalUnitAssignments()->where('is_active', true)->exists())->toBeFalse()
        ->and($managedUser->email_verified_at)->toBeNull()
        ->and($managedUser->password)->toBe($originalPasswordHash);
});

test('reviewers cannot update users', function () {
    $managedUser = User::factory()->departmentUser()->create([
        'name' => 'Department Planner',
    ]);
    $reviewer = User::factory()->reviewer()->create();
    $reviewerRole = Role::systemFor(UserRole::Executive);

    $response = $this
        ->actingAs($reviewer)
        ->patch(route('administration.users.update', [
            'current_team' => $reviewer->currentTeam,
            'user' => $managedUser,
        ]), [
            'name' => 'Changed Name',
            'email' => $managedUser->email,
            'role' => UserRole::Executive->value,
            'assignments' => [],
        ]);

    $response->assertForbidden();
    expect($managedUser->fresh()->name)->toBe('Department Planner');
});

test('custom catalog roles cannot be assigned through user administration', function () {
    $customRole = Role::factory()->withAccessLevel(UserRole::Executive)->create([
        'name' => 'Planning Officer',
        'slug' => 'planning-officer',
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'Planning Officer User',
            'email' => 'planning.officer@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role_id' => $customRole->id,
            'department_id' => null,
        ]);

    $response->assertSessionHasErrors('role_id');
    $this->assertDatabaseMissing('users', ['email' => 'planning.officer@example.com']);
});

test('custom staff catalog roles are rejected without creating an account', function () {
    $customRole = Role::factory()->withAccessLevel(UserRole::Staff)->create([
        'name' => 'Department Coordinator',
        'slug' => 'department-coordinator',
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'Department Coordinator User',
            'email' => 'coordinator@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role_id' => $customRole->id,
            'department_id' => null,
        ]);

    $response->assertSessionHasErrors('assignments');
    $this->assertDatabaseMissing('users', ['email' => 'coordinator@example.com']);
});

test('user administration rejects retired catalog authorization fields', function () {
    $reviewerRole = Role::systemFor(UserRole::Executive);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.users.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'Injected Administrator',
            'email' => 'injected@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'role_id' => $reviewerRole->id,
            'role' => UserRole::SystemAdmin->value,
            'department_id' => null,
        ]);

    $response->assertSessionHasErrors('role_id');
    $this->assertDatabaseMissing('users', ['email' => 'injected@example.com']);
});

test('users have no destructive delete route', function () {
    expect(Route::has('administration.users.destroy'))->toBeFalse();
});
