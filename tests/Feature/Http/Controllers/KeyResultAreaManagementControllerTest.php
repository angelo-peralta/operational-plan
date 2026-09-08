<?php

use App\Models\KeyResultAreaTemplate;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('system administrator can navigate to global KRA Management', function () {
    KeyResultAreaTemplate::query()->delete();
    $template = KeyResultAreaTemplate::factory()->create([
        'code' => 'KRA 2',
        'name' => 'Research and Innovation',
        'sort_order' => 2,
    ]);
    $administrator = User::factory()->systemAdmin()->create();

    $response = $this->actingAs($administrator)
        ->get(route('key-results.index', ['current_team' => $administrator->currentTeam]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('administration/key-results/index')
        ->has('templates', 1)
        ->where('templates.0.id', $template->id)
        ->where('templates.0.name', 'Research and Innovation')
        ->where('templates.0.isActive', true)
        ->missing('academicYear')
        ->missing('operationalPlans'));
});

test('executives cannot open system KRA Management', function () {
    $organizationalUnit = OrganizationalUnit::factory()->create();
    $executive = User::factory()->executive()->assignedToUnit($organizationalUnit)->create();

    $this->actingAs($executive)
        ->get(route('key-results.index', ['current_team' => $executive->currentTeam]))
        ->assertForbidden();
});
