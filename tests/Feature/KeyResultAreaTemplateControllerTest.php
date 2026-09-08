<?php

use App\Enums\OperationalPlanStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\KeyResultAreaTemplate;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Database\Seeders\KeyResultAreaTemplateSeeder;

function keyResultAreaTemplatePayload(array $overrides = []): array
{
    return [
        'code' => 'KRA 2',
        'name' => 'Research and Innovation',
        'description' => 'Advance relevant research.',
        'sort_order' => 2,
        'is_active' => true,
        ...$overrides,
    ];
}

test('system administrators create update and delete global KRA templates', function () {
    $administrator = User::factory()->systemAdmin()->create();

    $createResponse = $this->actingAs($administrator)
        ->post(route('key-results.store', ['current_team' => $administrator->currentTeam]), keyResultAreaTemplatePayload([
            'code' => '  kra 2  ',
            'name' => '  Research   and Innovation  ',
            'description' => '  Advance relevant research.  ',
        ]));

    $template = KeyResultAreaTemplate::query()->where('code', 'KRA 2')->sole();
    $createResponse->assertSessionHasNoErrors()->assertRedirect();
    expect($template)
        ->name->toBe('Research and Innovation')
        ->description->toBe('Advance relevant research.')
        ->sort_order->toBe(2)
        ->is_active->toBeTrue()
        ->created_by->toBe($administrator->id)
        ->updated_by->toBe($administrator->id);

    $updateResponse = $this->actingAs($administrator)
        ->patch(route('key-results.update', [
            'current_team' => $administrator->currentTeam,
            'key_result_area_template' => $template,
        ]), keyResultAreaTemplatePayload([
            'code' => 'KRA 2',
            'name' => 'Research, Innovation, and Extension',
            'description' => null,
            'sort_order' => 3,
            'is_active' => false,
        ]));

    $updateResponse->assertSessionHasNoErrors()->assertRedirect();
    expect($template->fresh())
        ->name->toBe('Research, Innovation, and Extension')
        ->description->toBeNull()
        ->sort_order->toBe(3)
        ->is_active->toBeFalse()
        ->created_by->toBe($administrator->id)
        ->updated_by->toBe($administrator->id);

    $deleteResponse = $this->actingAs($administrator)
        ->delete(route('key-results.destroy', [
            'current_team' => $administrator->currentTeam,
            'key_result_area_template' => $template,
        ]));

    $deleteResponse->assertSessionHasNoErrors()->assertRedirect();
    $this->assertDatabaseMissing('key_result_area_templates', ['id' => $template->id]);
});

test('global KRA templates validate unique codes and protected audit fields', function () {
    $administrator = User::factory()->systemAdmin()->create();
    KeyResultAreaTemplate::factory()->create(['code' => 'KRA 2']);

    $response = $this->actingAs($administrator)
        ->post(route('key-results.store', ['current_team' => $administrator->currentTeam]), keyResultAreaTemplatePayload([
            'code' => 'kra 2',
            'created_by' => User::factory()->systemAdmin()->create()->id,
            'updated_by' => User::factory()->systemAdmin()->create()->id,
        ]));

    $response->assertSessionHasErrors(['code', 'created_by', 'updated_by']);
    $this->assertDatabaseCount('key_result_area_templates', 2);
});

test('non-administrators cannot mutate global KRA templates', function () {
    $organizationalUnit = OrganizationalUnit::factory()->create();
    $executive = User::factory()->executive()->assignedToUnit($organizationalUnit)->create();
    $template = KeyResultAreaTemplate::query()->firstOrFail();

    $this->actingAs($executive)
        ->post(route('key-results.store', ['current_team' => $executive->currentTeam]), keyResultAreaTemplatePayload())
        ->assertForbidden();
    $this->actingAs($executive)
        ->patch(route('key-results.update', [
            'current_team' => $executive->currentTeam,
            'key_result_area_template' => $template,
        ]), keyResultAreaTemplatePayload())
        ->assertForbidden();
    $this->actingAs($executive)
        ->delete(route('key-results.destroy', [
            'current_team' => $executive->currentTeam,
            'key_result_area_template' => $template,
        ]))
        ->assertForbidden();
});

test('at least one active global KRA template is preserved', function () {
    $administrator = User::factory()->systemAdmin()->create();
    $template = KeyResultAreaTemplate::query()->sole();

    $deactivateResponse = $this->actingAs($administrator)
        ->patch(route('key-results.update', [
            'current_team' => $administrator->currentTeam,
            'key_result_area_template' => $template,
        ]), keyResultAreaTemplatePayload([
            'code' => $template->code,
            'name' => $template->name,
            'sort_order' => $template->sort_order,
            'is_active' => false,
        ]));

    $deactivateResponse->assertSessionHasErrors([
        'is_active' => 'At least one global KRA template must remain active.',
    ]);

    $deleteResponse = $this->actingAs($administrator)
        ->delete(route('key-results.destroy', [
            'current_team' => $administrator->currentTeam,
            'key_result_area_template' => $template,
        ]));

    $deleteResponse->assertSessionHasErrors([
        'template' => 'At least one global KRA template must remain active.',
    ]);
    expect($template->fresh())->not->toBeNull()->is_active->toBeTrue();
});

test('new Operational Plans receive active global KRA snapshots in display order', function () {
    KeyResultAreaTemplate::query()->delete();
    $secondTemplate = KeyResultAreaTemplate::factory()->create([
        'code' => 'KRA 2',
        'name' => 'Research and Innovation',
        'description' => 'Original research description.',
        'sort_order' => 20,
    ]);
    $firstTemplate = KeyResultAreaTemplate::factory()->create([
        'code' => 'KRA 1',
        'name' => 'Quality Teaching',
        'sort_order' => 10,
    ]);
    KeyResultAreaTemplate::factory()->inactive()->create([
        'code' => 'KRA 3',
        'name' => 'Inactive Future Area',
        'sort_order' => 5,
    ]);
    $academicYear = AcademicYear::factory()->open()->create();
    $firstUnit = OrganizationalUnit::factory()->create();
    $secondUnit = OrganizationalUnit::factory()->create();
    $administrator = User::factory()->systemAdmin()->create();

    $this->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $administrator->currentTeam]), [
            'organizational_unit_id' => $firstUnit->id,
            'accountable_name' => 'First Unit Head',
            'goal' => 'First annual goal.',
        ])
        ->assertSessionHasNoErrors();

    $firstPlan = OperationalPlan::query()->whereBelongsTo($firstUnit, 'organizationalUnit')->sole();
    expect($firstPlan->keyResultAreas()->pluck('name')->all())->toBe([
        'Quality Teaching',
        'Research and Innovation',
    ]);
    expect($firstPlan->keyResultAreas()->pluck('created_by')->unique()->all())->toBe([$administrator->id]);

    $history = OperationalPlanStatusHistory::query()
        ->whereBelongsTo($firstPlan)
        ->where('to_status', OperationalPlanStatus::Draft)
        ->sole();
    expect($history->snapshot)->toHaveKey('key_result_areas.0.name', 'Quality Teaching')
        ->toHaveKey('key_result_areas.1.name', 'Research and Innovation');

    $firstTemplate->update(['name' => 'Revised Quality Teaching']);
    $secondTemplate->delete();

    $this->actingAs($administrator)
        ->withSession([ResolveAcademicYearContext::SESSION_KEY => $academicYear->id])
        ->post(route('operational-plans.store', ['current_team' => $administrator->currentTeam]), [
            'organizational_unit_id' => $secondUnit->id,
            'accountable_name' => 'Second Unit Head',
            'goal' => 'Second annual goal.',
        ])
        ->assertSessionHasNoErrors();

    $secondPlan = OperationalPlan::query()->whereBelongsTo($secondUnit, 'organizationalUnit')->sole();
    expect($firstPlan->keyResultAreas()->pluck('name')->all())->toBe([
        'Quality Teaching',
        'Research and Innovation',
    ])->and($secondPlan->keyResultAreas()->pluck('name')->all())->toBe([
        'Revised Quality Teaching',
    ]);
});

test('the default KRA seeder is idempotent and preserves administrator changes', function () {
    $template = KeyResultAreaTemplate::query()->where('code', 'KRA 1')->sole();
    $template->update(['name' => 'Administrator revised name']);

    $this->seed(KeyResultAreaTemplateSeeder::class);
    $this->seed(KeyResultAreaTemplateSeeder::class);

    expect(KeyResultAreaTemplate::query()->where('code', 'KRA 1')->sole()->name)
        ->toBe('Administrator revised name');
});
