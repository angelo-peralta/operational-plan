<?php

namespace Database\Factories;

use App\Models\KeyResultArea;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlanItem>
 */
class PlanItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key_result_area_id' => KeyResultArea::factory(),
            'objective' => fake()->sentence(),
            'strategy' => fake()->optional()->sentence(),
            'kpi_target_text' => fake()->sentence(),
            'target_value' => null,
            'target_unit' => null,
            'target_operator' => null,
            'target_frequency' => null,
            'resources_needed' => fake()->optional()->sentence(),
            'documentary_evidence_requirements' => [fake()->sentence(3)],
            'manual_co_accountable_units' => [],
            'sort_order' => 1,
            'created_by' => User::factory()->superAdmin(),
            'updated_by' => User::factory()->superAdmin(),
        ];
    }
}
