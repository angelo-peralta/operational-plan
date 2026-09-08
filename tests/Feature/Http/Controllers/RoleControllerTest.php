<?php

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

test('super administrators can view operational roles', function () {
    $customRole = Role::factory()->withAccessLevel(UserRole::Executive)->create([
        'name' => 'Planning Officer',
        'slug' => 'planning-officer',
        'description' => 'Reviews submitted plans.',
    ]);
    User::factory()->forOperationalRole($customRole)->create();
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->get(route('administration.roles.index', ['current_team' => $administrator->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('administration/roles/index')
        ->has('roles', 4)
        ->where('roles', UserRole::options()),
    );
});

test('guests are redirected from operational role management', function () {
    $response = $this->get(route('administration.roles.index', ['current_team' => 'spup-ops']));

    $response->assertRedirectToRoute('login');
});

test('reviewers cannot view operational role management', function () {
    $reviewer = User::factory()->reviewer()->create();

    $response = $this
        ->actingAs($reviewer)
        ->get(route('administration.roles.index', ['current_team' => $reviewer->currentTeam]));

    $response->assertForbidden();
});

test('system administrators cannot create additional operational roles', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.roles.store', ['current_team' => $administrator->currentTeam]), [
            'name' => '  Planning   Officer  ',
            'description' => ' Reviews submitted plans. ',
            'access_level' => UserRole::Executive->value,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('roles', ['slug' => 'planning-officer']);
});

test('department users cannot create operational roles', function () {
    $departmentUser = User::factory()->departmentUser()->create();

    $response = $this
        ->actingAs($departmentUser)
        ->post(route('administration.roles.store', ['current_team' => $departmentUser->currentTeam]), [
            'name' => 'Department Head',
            'access_level' => UserRole::Staff->value,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('roles', ['slug' => 'department-head']);
});

test('role creation rejects invalid access and protected attributes', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.roles.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'Injected Role',
            'access_level' => 'owner',
            'slug' => 'super_admin',
            'is_system' => true,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('roles', ['name' => 'Injected Role']);
});

test('role creation rejects names that normalize to an existing slug', function () {
    Role::factory()->create([
        'name' => 'Planning-Officer',
        'slug' => 'planning-officer',
    ]);
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.roles.store', ['current_team' => $administrator->currentTeam]), [
            'name' => 'Planning Officer!',
            'access_level' => UserRole::Executive->value,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('roles', 8);
});

test('role creation rejects names that generate an oversized identifier', function () {
    $administrator = User::factory()->superAdmin()->create();

    $response = $this
        ->actingAs($administrator)
        ->post(route('administration.roles.store', ['current_team' => $administrator->currentTeam]), [
            'name' => str_repeat('ß', 255),
            'access_level' => UserRole::Executive->value,
        ]);

    $response->assertForbidden();
    $this->assertDatabaseCount('roles', 7);
});

test('operational roles have no destructive or access-changing routes', function () {
    expect(Route::has('administration.roles.update'))->toBeFalse()
        ->and(Route::has('administration.roles.destroy'))->toBeFalse();
});
