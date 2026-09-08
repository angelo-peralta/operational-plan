<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $systemRoles = [
            'super_admin' => 'Super Admin',
            'reviewer' => 'Reviewer',
            'department_user' => 'Department User',
        ];

        $unmappedRoleExists = DB::table('users')
            ->whereNotIn('role', array_keys($systemRoles))
            ->exists();

        if ($unmappedRoleExists) {
            throw new RuntimeException('Existing users contain an unsupported operational role.');
        }

        $timestamp = now();

        foreach ($systemRoles as $slug => $name) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => "Built-in {$name} access profile.",
                    'access_level' => $slug,
                    'is_system' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ],
            );

            $roleId = DB::table('roles')->where('slug', $slug)->value('id');

            DB::table('users')
                ->where('role', $slug)
                ->whereNull('role_id')
                ->update(['role_id' => $roleId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $systemRoleIds = DB::table('roles')
            ->where('is_system', true)
            ->pluck('id');

        DB::table('users')
            ->whereIn('role_id', $systemRoleIds)
            ->update(['role_id' => null]);

        DB::table('roles')->where('is_system', true)->delete();
    }
};
