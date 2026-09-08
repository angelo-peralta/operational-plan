<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $accounts = [
            ['Demo System Admin', 'superadmin@example.com', UserRole::SystemAdmin, null, null],
            ['Demo President', 'president@example.com', UserRole::Executive, 'SPUP', 'University President'],
            ['Demo VP Academic', 'vp.academic@example.com', UserRole::Executive, 'ACADEMIC', 'Vice President for Academic Affairs'],
            ['Demo VP Administration', 'vp.admin@example.com', UserRole::Executive, 'ADMIN', 'Vice President for Administration'],
            ['Demo SITE Dean', 'dean@example.com', UserRole::OfficeHead, 'SITE', 'School Dean'],
            ['Demo SITE Secretary', 'staff@example.com', UserRole::Staff, 'SITE', 'Secretary'],
            ['Demo CPRINT Staff', 'department@example.com', UserRole::Staff, 'CPRINT', 'Administrative Assistant'],
        ];
        $created = [];

        foreach ($accounts as [$name, $email, $role, $unitCode, $title]) {
            if (User::query()->where('email', $email)->exists()) {
                continue;
            }
            $factory = User::factory()->forOperationalRole(Role::systemFor($role));
            if ($unitCode !== null) {
                $unit = OrganizationalUnit::query()->where('code', $unitCode)->firstOrFail();
                $factory = $factory->assignedToUnit($unit);
            }
            $user = $factory->create(['name' => $name, 'email' => $email, 'password' => 'password']);
            $user->organizationalUnitAssignments()->update(['position_title' => $title]);
            $created[] = [$role->label(), $email, 'password'];
        }

        if ($created !== []) {
            $this->command->warn('Development/demo credentials only. Existing accounts and passwords are preserved.');
            $this->command->table(['Role', 'Email', 'Password'], $created);
        }
    }
}
