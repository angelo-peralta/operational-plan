<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = [
            'system_admin' => 'System Admin',
            'executive' => 'Executive',
            'office_head' => 'Office Head',
            'staff' => 'Secretary / Staff',
        ];
        $timestamp = now();
        $legacyRoles = DB::table('roles')->pluck('access_level', 'id');

        DB::table('roles')->update(['is_active' => false]);
        foreach ($roles as $slug => $label) {
            DB::table('roles')->updateOrInsert(['slug' => $slug], [
                'name' => $label,
                'access_level' => $slug,
                'description' => 'Revised MVP system role.',
                'is_system' => true,
                'is_active' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        }

        $roleIds = DB::table('roles')->where('is_active', true)->pluck('id', 'slug');

        DB::table('users')->orderBy('id')->each(function (object $user) use ($roles, $roleIds, $legacyRoles, $timestamp): void {
            $oldRole = $user->role_id !== null ? ($legacyRoles[$user->role_id] ?? $user->role) : $user->role;
            $role = array_key_exists($oldRole, $roles) ? $oldRole : ($oldRole === 'super_admin' ? 'system_admin' : 'staff');

            DB::table('users')->where('id', $user->id)->update([
                'legacy_role' => $user->role,
                'legacy_role_id' => $user->role_id,
                'role' => $role,
                'role_id' => $roleIds[$role],
            ]);

            if ($user->department_id !== null && $role !== 'system_admin') {
                DB::table('organizational_unit_user_assignments')->insert([
                    'user_id' => $user->id,
                    'organizational_unit_id' => $user->department_id,
                    'assignment_type' => match ($role) {
                        'executive' => 'executive',
                        'office_head' => 'head',
                        default => 'staff',
                    },
                    'is_primary' => true,
                    'is_active' => true,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }
        });

        DB::table('accomplishments')
            ->whereIn('status', ['queued_for_analysis', 'analyzing', 'ready_for_review'])
            ->update(['status' => 'under_review']);
    }

    public function down(): void
    {
        throw new RuntimeException('Role assignments and historical provenance require a forward correction; automatic rollback would lose authority changes.');
    }
};
