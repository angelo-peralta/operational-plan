<?php

namespace Database\Factories;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AcademicYear>
 */
class AcademicYearFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = fake()->unique()->numberBetween(2000, 2100);
        $endYear = $startYear + 1;

        return [
            'name' => "AY {$startYear}-{$endYear}",
            'start_year' => $startYear,
            'end_year' => $endYear,
            'starts_on' => Carbon::create($startYear, 6, 1),
            'ends_on' => Carbon::create($endYear, 5, 31),
            'status' => AcademicYearStatus::Draft,
            'is_current' => false,
        ];
    }

    /**
     * Indicate that the academic year is open.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AcademicYearStatus::Open,
        ]);
    }

    /**
     * Indicate that the academic year is closed.
     */
    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AcademicYearStatus::Closed,
        ]);
    }

    /**
     * Indicate that the academic year is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AcademicYearStatus::Archived,
        ]);
    }

    /**
     * Indicate that the academic year is the current open year.
     */
    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AcademicYearStatus::Open,
            'is_current' => true,
        ]);
    }
}
