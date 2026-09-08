<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(rtrim(fake()->unique()->sentence(3), '.'));

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->optional()->sentence(),
            'access_level' => fake()->randomElement(UserRole::cases()),
            'is_system' => false,
        ];
    }

    public function withAccessLevel(UserRole $accessLevel): static
    {
        return $this->state(fn (array $attributes): array => [
            'access_level' => $accessLevel,
        ]);
    }

    public function system(UserRole $accessLevel): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $accessLevel->label(),
            'slug' => $accessLevel->value,
            'access_level' => $accessLevel,
            'is_system' => true,
        ]);
    }
}
