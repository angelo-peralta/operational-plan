<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('ownership migration renames active columns without losing plan or co-accountable links', function () {
    $originalConnection = DB::getDefaultConnection();
    config(['database.connections.phase_two_ownership_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('phase_two_ownership_test');

    try {
        $ownershipPath = database_path('migrations/2026_09_07_001936_revise_operational_plan_organizational_ownership.php');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            if (basename($path) >= basename($ownershipPath)) {
                continue;
            }
            (require $path)->up();
        }

        DB::table('organizational_units')->insert(['id' => 7, 'name' => 'Preserved Unit', 'type' => 'office']);
        DB::table('users')->insert(['id' => 3, 'name' => 'Owner', 'email' => 'owner@example.com', 'password' => 'hash', 'role' => 'staff']);
        DB::table('academic_years')->insert(['id' => 5, 'name' => 'AY', 'start_year' => 2026, 'end_year' => 2027, 'status' => 'open']);
        DB::table('operational_plans')->insert(['id' => 11, 'academic_year_id' => 5, 'department_id' => 7, 'goal' => 'Preserved', 'status' => 'draft', 'created_by' => 3, 'updated_by' => 3]);
        DB::table('key_result_areas')->insert(['id' => 12, 'operational_plan_id' => 11, 'name' => 'KRA', 'sort_order' => 1, 'created_by' => 3, 'updated_by' => 3]);
        DB::table('plan_items')->insert(['id' => 13, 'key_result_area_id' => 12, 'objective' => 'Objective', 'kpi_target_text' => 'Target', 'sort_order' => 1, 'created_by' => 3, 'updated_by' => 3]);
        DB::table('plan_item_co_accountables')->insert(['plan_item_id' => 13, 'department_id' => 7]);

        (require $ownershipPath)->up();

        expect(Schema::hasColumn('operational_plans', 'department_id'))->toBeFalse()
            ->and(DB::table('operational_plans')->where('id', 11)->value('organizational_unit_id'))->toBe(7)
            ->and(Schema::hasColumn('plan_item_co_accountables', 'department_id'))->toBeFalse()
            ->and(DB::table('plan_item_co_accountables')->where('plan_item_id', 13)->value('organizational_unit_id'))->toBe(7);
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('phase_two_ownership_test');
    }
});
