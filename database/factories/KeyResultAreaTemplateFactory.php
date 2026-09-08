<?php

namespace Database\Factories;

use App\Models\KeyResultAreaTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KeyResultAreaTemplate>
 */
class KeyResultAreaTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'KRA '.fake()->unique()->numberBetween(1, 9999),
            'name' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
