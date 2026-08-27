<?php

namespace Database\Factories;

use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OperationalPlan>
 */
class OperationalPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory()->open(),
            'department_id' => Department::factory(),
            'accountable_user_id' => null,
            'accountable_name' => fake()->name(),
            'accountable_position' => fake()->jobTitle(),
            'goal' => fake()->paragraph(),
            'status' => OperationalPlanStatus::Draft,
            'created_by' => User::factory()->superAdmin(),
            'updated_by' => User::factory()->superAdmin(),
            'submitted_by' => null,
            'submitted_at' => null,
            'approved_by' => null,
            'approved_at' => null,
            'returned_by' => null,
            'returned_at' => null,
            'closed_by' => null,
            'closed_at' => null,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OperationalPlanStatus::Submitted,
            'submitted_by' => User::factory()->departmentUser(),
            'submitted_at' => now(),
        ]);
    }

    public function approved(): static
    {
        return $this->submitted()->state(fn (array $attributes): array => [
            'status' => OperationalPlanStatus::Approved,
            'approved_by' => User::factory()->reviewer(),
            'approved_at' => now(),
        ]);
    }

    public function returned(): static
    {
        return $this->submitted()->state(fn (array $attributes): array => [
            'status' => OperationalPlanStatus::Returned,
            'returned_by' => User::factory()->reviewer(),
            'returned_at' => now(),
        ]);
    }

    public function closed(): static
    {
        return $this->approved()->state(fn (array $attributes): array => [
            'status' => OperationalPlanStatus::Closed,
            'closed_by' => User::factory()->superAdmin(),
            'closed_at' => now(),
        ]);
    }
}
