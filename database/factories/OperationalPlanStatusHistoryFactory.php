<?php

namespace Database\Factories;

use App\Enums\OperationalPlanStatus;
use App\Models\OperationalPlan;
use App\Models\OperationalPlanStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalPlanStatusHistory>
 */
class OperationalPlanStatusHistoryFactory extends Factory
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
            'actor_id' => User::factory()->superAdmin(),
            'from_status' => OperationalPlanStatus::Draft,
            'to_status' => OperationalPlanStatus::Submitted,
            'remarks' => null,
            'snapshot' => null,
            'created_at' => now(),
        ];
    }
}
