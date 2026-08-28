<?php

namespace Database\Factories;

use App\Enums\AccomplishmentStatus;
use App\Models\Accomplishment;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accomplishment>
 */
class AccomplishmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plan_item_id' => PlanItem::factory(),
            'reporting_period_id' => ReportingPeriod::factory(),
            'reported_value' => null,
            'accomplishment_text' => fake()->sentence(),
            'percentage_accomplished' => null,
            'status' => AccomplishmentStatus::Draft,
            'submitted_by' => null,
            'submitted_at' => null,
            'resubmitted_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AccomplishmentStatus::Submitted,
            'submitted_by' => User::factory()->departmentUser(),
            'submitted_at' => now(),
        ]);
    }

    public function returned(): static
    {
        return $this->submitted()->state(fn (array $attributes): array => [
            'status' => AccomplishmentStatus::Returned,
        ]);
    }
}
