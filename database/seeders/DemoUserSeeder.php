<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $planningOffice = Department::query()->where('code', 'PLAN')->firstOrFail();
        $department = Department::query()->where('code', 'CPRINT')->firstOrFail();

        $createdAccounts = [];

        if ($this->seedDemoUser(
            name: 'Demo Super Admin',
            email: 'superadmin@example.com',
            role: UserRole::SuperAdmin,
        )) {
            $createdAccounts[] = ['Super Admin', 'superadmin@example.com', 'password'];
        }

        if ($this->seedDemoUser(
            name: 'Demo Reviewer',
            email: 'reviewer@example.com',
            role: UserRole::Reviewer,
            department: $planningOffice,
        )) {
            $createdAccounts[] = ['Reviewer', 'reviewer@example.com', 'password'];
        }

        if ($this->seedDemoUser(
            name: 'Demo Department User',
            email: 'department@example.com',
            role: UserRole::DepartmentUser,
            department: $department,
        )) {
            $createdAccounts[] = ['Department User', 'department@example.com', 'password'];
        }

        if ($createdAccounts === []) {
            $this->command->info('Development/demo accounts already exist; no credentials were changed.');

            return;
        }

        $this->command->newLine();
        $this->command->warn('Development/demo accounts only');
        $this->command->table(
            ['Role', 'Email', 'Password'],
            $createdAccounts,
        );
    }

    /**
     * Create or refresh a local demo account.
     */
    private function seedDemoUser(
        string $name,
        string $email,
        UserRole $role,
        ?Department $department = null,
    ): bool {
        $user = User::query()->where('email', $email)->first();

        if ($user !== null) {
            return false;
        }

        $factory = match ($role) {
            UserRole::SuperAdmin => User::factory()->superAdmin(),
            UserRole::Reviewer => User::factory()->reviewer(),
            UserRole::DepartmentUser => User::factory()->departmentUser(),
        };

        if ($department !== null) {
            $factory = $factory->forDepartment($department);
        }

        $factory->create([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        return true;
    }
}
