<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('forward migration preserves legacy users unit ownership and history while retiring old authority', function () {
    $originalConnection = DB::getDefaultConnection();
    config(['database.connections.legacy_foundation_test' => [
        'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
    ]]);
    DB::setDefaultConnection('legacy_foundation_test');

    try {
        $schemaPath = database_path('migrations/2026_09_05_111038_revise_organizational_foundation_schema.php');
        $dataPath = database_path('migrations/2026_09_05_111039_migrate_legacy_organizational_foundation.php');
        foreach (glob(database_path('migrations/*.php')) as $path) {
            if (basename($path) >= basename($schemaPath)) {
                continue;
            }
            (require $path)->up();
        }

        DB::table('departments')->insert(['id' => 7, 'name' => 'Preserved Office', 'code' => 'OLD']);
        foreach (['super_admin', 'reviewer', 'department_user'] as $index => $role) {
            DB::table('users')->insert([
                'id' => $index + 1, 'name' => 'Legacy '.$role,
                'email' => $role.'@example.com', 'password' => 'unchanged-password-hash',
                'role' => $role, 'role_id' => DB::table('roles')->where('slug', $role)->value('id'),
                'department_id' => $index === 0 ? null : 7,
            ]);
        }
        DB::table('academic_years')->insert(['id' => 3, 'name' => 'AY 2025-2026', 'start_year' => 2025, 'end_year' => 2026, 'status' => 'open']);
        DB::table('reporting_periods')->insert(['id' => 4, 'academic_year_id' => 3, 'name' => 'First Semester', 'code' => 'first_semester', 'sequence' => 1, 'status' => 'open']);
        DB::table('operational_plans')->insert(['id' => 11, 'academic_year_id' => 3, 'department_id' => 7, 'goal' => 'Keep this goal', 'status' => 'approved', 'created_by' => 3, 'updated_by' => 3]);
        $snapshot = '{"department":{"id":7},"goal":"Keep this goal"}';
        DB::table('operational_plan_status_histories')->insert(['operational_plan_id' => 11, 'actor_id' => 2, 'to_status' => 'approved', 'snapshot' => $snapshot]);
        DB::table('key_result_areas')->insert(['id' => 12, 'operational_plan_id' => 11, 'name' => 'Preserved KRA', 'sort_order' => 1, 'created_by' => 3, 'updated_by' => 3]);
        DB::table('plan_items')->insert(['id' => 13, 'key_result_area_id' => 12, 'objective' => 'Preserved objective', 'kpi_target_text' => 'Qualitative target', 'sort_order' => 1, 'created_by' => 3, 'updated_by' => 3]);
        DB::table('plan_item_co_accountables')->insert(['plan_item_id' => 13, 'department_id' => 7]);
        DB::table('accomplishments')->insert(['id' => 14, 'plan_item_id' => 13, 'reporting_period_id' => 4, 'status' => 'ready_for_review']);
        DB::table('accomplishment_submissions')->insert(['id' => 15, 'accomplishment_id' => 14, 'revision' => 1, 'submitted_by' => 3, 'from_status' => 'draft', 'to_status' => 'ready_for_review', 'evidence_count' => 0, 'snapshot' => $snapshot]);
        DB::table('evaluations')->insert(['accomplishment_submission_id' => 15, 'reviewer_id' => 2, 'decision' => 'accepted', 'rating' => 2, 'remarks' => 'Preserved review', 'reviewed_at' => now()]);

        (require $schemaPath)->up();
        (require $dataPath)->up();

        expect(Schema::hasTable('departments'))->toBeFalse()
            ->and(DB::table('organizational_units')->where('id', 7)->value('name'))->toBe('Preserved Office')
            ->and(DB::table('users')->orderBy('id')->pluck('role')->all())->toBe(['system_admin', 'staff', 'staff'])
            ->and(DB::table('users')->where('id', 2)->value('legacy_role'))->toBe('reviewer')
            ->and(DB::table('users')->where('id', 2)->value('password'))->toBe('unchanged-password-hash')
            ->and(DB::table('organizational_unit_user_assignments')->where('assignment_type', 'staff')->count())->toBe(2)
            ->and(DB::table('organizational_unit_user_assignments')->where('assignment_type', 'executive')->count())->toBe(0)
            ->and(DB::table('roles')->where('is_active', true)->count())->toBe(4)
            ->and(DB::table('roles')->where('slug', 'reviewer')->value('is_active'))->toBe(0)
            ->and(DB::table('operational_plans')->where('id', 11)->value('department_id'))->toBe(7)
            ->and(DB::table('operational_plan_status_histories')->value('snapshot'))->toBe($snapshot)
            ->and(DB::table('plan_item_co_accountables')->value('department_id'))->toBe(7)
            ->and(DB::table('accomplishments')->where('id', 14)->value('status'))->toBe('under_review')
            ->and(DB::table('accomplishment_submissions')->where('id', 15)->value('to_status'))->toBe('ready_for_review')
            ->and(DB::table('accomplishment_submissions')->where('id', 15)->value('snapshot'))->toBe($snapshot)
            ->and(DB::table('evaluations')->value('remarks'))->toBe('Preserved review');
    } finally {
        DB::setDefaultConnection($originalConnection);
        DB::purge('legacy_foundation_test');
    }
});
