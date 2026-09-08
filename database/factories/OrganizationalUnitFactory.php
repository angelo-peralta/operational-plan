<?php

namespace Database\Factories;

use App\Enums\OrganizationalUnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationalUnit> */
class OrganizationalUnitFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'code' => fake()->unique()->bothify('UNIT-####-???'),
            'type' => OrganizationalUnitType::Office,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function university(): static
    {
        return $this->state(fn (): array => ['type' => OrganizationalUnitType::University, 'parent_id' => null]);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
