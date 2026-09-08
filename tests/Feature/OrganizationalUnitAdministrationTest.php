<?php

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\UserRole;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function organizationalUnitPayload(array $overrides = []): array
{
    return [...[
        'name' => 'Academic Affairs', 'code' => 'ACADEMIC', 'acronym' => 'VPA',
        'parent_id' => null, 'type' => 'vice_presidential_area', 'description' => null,
        'is_active' => true, 'sort_order' => 2,
    ], ...$overrides];
}

test('system admin configures a hierarchy and sees the four fixed roles', function () {
    $admin = User::factory()->systemAdmin()->create();
    $root = OrganizationalUnit::factory()->university()->create();

    $this->actingAs($admin)
        ->post(route('administration.organizational-units.store', ['current_team' => $admin->currentTeam]), organizationalUnitPayload(['parent_id' => $root->id]))
        ->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseHas('organizational_units', ['code' => 'ACADEMIC', 'parent_id' => $root->id]);
    $this->get(route('administration.organizational-units.index', ['current_team' => $admin->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page->component('administration/organizational-units/index')->has('organizationalUnits', 2));
    $this->get(route('administration.roles.index', ['current_team' => $admin->currentTeam]))
        ->assertInertia(fn (Assert $page) => $page->has('roles', 4)->where('roles.0.value', 'system_admin'));
    $this->post(route('administration.roles.store', ['current_team' => $admin->currentTeam]), [
        'name' => 'President', 'access_level' => 'executive',
    ])->assertForbidden();
});

test('only system admins can create organizational units', function (UserRole $role) {
    $user = User::factory()->create(['role' => $role]);

    $this->actingAs($user)->post(route('administration.organizational-units.store', ['current_team' => $user->currentTeam]), organizationalUnitPayload())
        ->assertForbidden();
    $this->assertDatabaseMissing('organizational_units', ['code' => 'ACADEMIC']);
})->with([UserRole::Executive, UserRole::OfficeHead, UserRole::Staff]);

test('hierarchy updates reject cycles while preserving existing parents', function () {
    $admin = User::factory()->systemAdmin()->create();
    $root = OrganizationalUnit::factory()->university()->create();
    $child = OrganizationalUnit::factory()->for($root, 'parent')->create();
    $grandchild = OrganizationalUnit::factory()->for($child, 'parent')->create();

    $this->actingAs($admin)->patch(route('administration.organizational-units.update', [
        'current_team' => $admin->currentTeam, 'organizational_unit' => $root,
    ]), organizationalUnitPayload(['name' => $root->name, 'code' => $root->code, 'parent_id' => $grandchild->id]))
        ->assertSessionHasErrors('parent_id');
    expect($root->refresh()->parent_id)->toBeNull();
});

test('administrator can give a head multiple assignments without granting authority from legacy fields', function () {
    $admin = User::factory()->systemAdmin()->create();
    $units = OrganizationalUnit::factory()->count(2)->create();
    $assignments = $units->map(fn ($unit, $index): array => [
        'organizational_unit_id' => $unit->id, 'assignment_type' => 'head',
        'position_title' => 'Director', 'is_primary' => $index === 0, 'is_active' => true,
        'starts_on' => null, 'ends_on' => null,
    ])->all();

    $this->actingAs($admin)->post(route('administration.users.store', ['current_team' => $admin->currentTeam]), [
        'name' => 'Multi Office Head', 'email' => 'head@example.com',
        'password' => 'A-strong-password-123', 'password_confirmation' => 'A-strong-password-123',
        'role' => 'office_head', 'assignments' => $assignments,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $user = User::query()->where('email', 'head@example.com')->firstOrFail();
    expect($user->role)->toBe(UserRole::OfficeHead)
        ->and($user->department_id)->toBeNull()
        ->and($user->organizationalUnitAssignments)->toHaveCount(2);
});

test('assignment validation rejects duplicate units primary conflicts dates and role mismatches', function (string $failure) {
    $admin = User::factory()->systemAdmin()->create();
    $managed = User::factory()->create();
    $first = OrganizationalUnit::factory()->create();
    $second = OrganizationalUnit::factory()->create();
    $assignment = [
        'organizational_unit_id' => $first->id, 'assignment_type' => 'staff', 'position_title' => null,
        'is_primary' => true, 'is_active' => true, 'starts_on' => null, 'ends_on' => null,
    ];
    $assignments = match ($failure) {
        'duplicate' => [$assignment, [...$assignment, 'is_primary' => false]],
        'primary' => [$assignment, [...$assignment, 'organizational_unit_id' => $second->id]],
        'dates' => [[...$assignment, 'starts_on' => '2026-10-01', 'ends_on' => '2026-09-01']],
        'type' => [[...$assignment, 'assignment_type' => 'executive']],
    };

    $this->actingAs($admin)->patch(route('administration.users.update', ['current_team' => $admin->currentTeam, 'user' => $managed]), [
        'name' => $managed->name, 'email' => $managed->email, 'role' => 'staff', 'assignments' => $assignments,
    ])->assertSessionHasErrors();

    expect($managed->organizationalUnitAssignments()->count())->toBe(0);
})->with(['duplicate', 'primary', 'dates', 'type']);

test('assignments may have only an end date and removed assignments retain their identity', function () {
    $admin = User::factory()->systemAdmin()->create();
    $unit = OrganizationalUnit::factory()->create();
    $managed = User::factory()->staff()->assignedToUnit($unit)->create();
    $assignmentId = $managed->organizationalUnitAssignments()->sole()->id;
    $url = route('administration.users.update', ['current_team' => $admin->currentTeam, 'user' => $managed]);
    $payload = ['name' => $managed->name, 'email' => $managed->email, 'role' => 'staff'];

    $this->actingAs($admin)->patch($url, [...$payload, 'assignments' => [[
        'organizational_unit_id' => $unit->id, 'assignment_type' => 'staff',
        'is_primary' => true, 'is_active' => true, 'starts_on' => null, 'ends_on' => '2027-05-31',
    ]]])->assertSessionHasNoErrors()->assertRedirect();
    expect($managed->organizationalUnitAssignments()->sole()->ends_on->toDateString())->toBe('2027-05-31');

    $this->patch($url, [...$payload, 'assignments' => []])->assertSessionHasNoErrors()->assertRedirect();
    expect($managed->organizationalUnitAssignments()->sole())
        ->id->toBe($assignmentId)->is_active->toBeFalse()->is_primary->toBeFalse();
    expect(app(ResolveOrganizationalScope::class)->visibleUnitIds($managed))->toBe([]);
});
