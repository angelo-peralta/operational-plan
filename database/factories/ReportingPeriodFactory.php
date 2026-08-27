<?php

namespace Database\Factories;

use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReportingPeriod>
 */
class ReportingPeriodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $words = fake()->unique()->words(2);
        $name = is_array($words) ? implode(' ', $words) : $words;

        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => Str::title($name),
            'code' => Str::slug($name, '_'),
            'sequence' => fake()->unique()->numberBetween(1, 1000),
            'starts_on' => null,
            'ends_on' => null,
            'status' => ReportingPeriodStatus::Draft,
        ];
    }

    /**
     * Indicate that the reporting period is the first semester.
     */
    public function firstSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'First Semester',
            'code' => 'first_semester',
            'sequence' => 1,
        ]);
    }

    /**
     * Indicate that the reporting period is the second semester.
     */
    public function secondSemester(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Second Semester',
            'code' => 'second_semester',
            'sequence' => 2,
        ]);
    }

    /**
     * Indicate that the reporting period is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportingPeriodStatus::Open,
        ]);
    }

    /**
     * Indicate that the reporting period is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportingPeriodStatus::Closed,
        ]);
    }
}
