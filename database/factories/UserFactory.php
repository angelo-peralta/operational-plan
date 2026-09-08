<?php

namespace Database\Factories;

use App\Enums\TeamRole;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $role = Role::systemFor(UserRole::Staff);

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'department_id' => null,
            'role_id' => $role->id,
            'role' => $role->access_level,
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterCreating(function ($user) {
            $team = Team::factory()->personal()->create([
                'name' => $user->name."'s Team",
            ]);

            $team->members()->attach($user, [
                'role' => TeamRole::Owner->value,
            ]);

            $user->switchTeam($team);

            if ($user->department_id !== null && $user->role->assignmentType() !== null) {
                $user->organizationalUnitAssignments()->create([
                    'organizational_unit_id' => $user->department_id,
                    'assignment_type' => $user->role->assignmentType(),
                    'is_primary' => true,
                ]);
            }
        });
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user is a super administrator.
     */
    public function superAdmin(): static
    {
        return $this->forOperationalRole(Role::systemFor(UserRole::SystemAdmin))
            ->state(fn (array $attributes): array => ['department_id' => null]);
    }

    /**
     * Indicate that the user is a reviewer.
     */
    public function reviewer(): static
    {
        return $this->forOperationalRole(Role::systemFor(UserRole::Executive))
            ->state(fn (array $attributes): array => ['department_id' => null]);
    }

    /**
     * Indicate that the user is a department user.
     */
    public function departmentUser(): static
    {
        return $this->forOperationalRole(Role::systemFor(UserRole::Staff))
            ->state(fn (array $attributes): array => ['department_id' => Department::factory()]);
    }

    public function systemAdmin(): static
    {
        return $this->superAdmin();
    }

    public function executive(): static
    {
        return $this->reviewer();
    }

    public function officeHead(): static
    {
        return $this->forOperationalRole(Role::systemFor(UserRole::OfficeHead))
            ->state(fn (): array => ['department_id' => Department::factory()]);
    }

    public function staff(): static
    {
        return $this->departmentUser();
    }

    public function assignedToUnit(OrganizationalUnit|Department $unit): static
    {
        return $this->state(fn (): array => ['department_id' => $unit->id]);
    }

    public function executiveOver(OrganizationalUnit|Department ...$units): static
    {
        $root = OrganizationalUnit::factory()->university()->create();

        foreach ($units as $unit) {
            OrganizationalUnit::query()->whereKey($unit->id)->update(['parent_id' => $root->id]);
        }

        return $this->executive()->assignedToUnit($root);
    }

    public function forOperationalRole(Role $role): static
    {
        return $this->state(fn (array $attributes): array => [
            'role_id' => $role->id,
            'role' => $role->access_level,
        ]);
    }

    /**
     * Assign the user to a department.
     */
    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department_id' => $department->getKey(),
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
