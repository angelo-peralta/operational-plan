<?php

namespace Database\Factories;

use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeyResultArea>
 */
class KeyResultAreaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'operational_plan_id' => OperationalPlan::factory(),
            'code' => 'KRA '.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => 1,
            'created_by' => User::factory()->superAdmin(),
            'updated_by' => User::factory()->superAdmin(),
        ];
    }
}
