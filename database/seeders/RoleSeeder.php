<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $accessLevel) {
            Role::query()->updateOrCreate(
                ['slug' => $accessLevel->value],
                [
                    'name' => $accessLevel->label(),
                    'description' => "Built-in {$accessLevel->label()} access profile.",
                    'access_level' => $accessLevel,
                    'is_system' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
