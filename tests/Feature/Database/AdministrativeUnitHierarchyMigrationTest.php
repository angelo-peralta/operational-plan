<?php

use App\Enums\OrganizationalUnitType;
use App\Models\OrganizationalUnit;

test('forward migration reparents original root administrative units without overriding later hierarchy changes', function () {
    $university = OrganizationalUnit::factory()->university()->create(['code' => 'SPUP']);
    $administrativeArea = OrganizationalUnit::factory()->for($university, 'parent')->create([
        'code' => 'ADMIN',
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);
    $alternateArea = OrganizationalUnit::factory()->for($university, 'parent')->create([
        'code' => 'OTHER-AREA',
        'type' => OrganizationalUnitType::VicePresidentialArea,
    ]);

    foreach (['CPRINT', 'ICT', 'QA'] as $code) {
        OrganizationalUnit::factory()->for($university, 'parent')->create(['code' => $code]);
    }

    OrganizationalUnit::factory()->for($alternateArea, 'parent')->create(['code' => 'HR']);
    $unrelatedUnit = OrganizationalUnit::factory()->for($university, 'parent')->create(['code' => 'ACADEMIC']);

    (require database_path('migrations/2026_09_08_012053_reparent_administrative_units_under_administrative_and_general_services.php'))->up();

    expect(OrganizationalUnit::query()->whereIn('code', ['CPRINT', 'ICT', 'QA'])->pluck('parent_id')->all())
        ->each->toBe($administrativeArea->id);
    expect(OrganizationalUnit::query()->where('code', 'HR')->value('parent_id'))->toBe($alternateArea->id);
    expect($unrelatedUnit->fresh()->parent_id)->toBe($university->id);
});
