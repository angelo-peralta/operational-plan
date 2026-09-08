<?php

namespace Database\Factories;

use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evaluation>
 */
class EvaluationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accomplishment_submission_id' => AccomplishmentSubmission::factory(),
            'reviewer_id' => User::factory()->reviewer(),
            'organizational_unit_id' => null,
            'decision' => EvaluationDecision::Accepted,
            'rating' => EvaluationRating::Met,
            'remarks' => null,
            'is_override' => false,
            'overrides_evaluation_id' => null,
            'reviewed_at' => now(),
            'created_at' => now(),
        ];
    }

    public function requestAdditionalEvidence(): static
    {
        return $this->state(fn (array $attributes): array => [
            'decision' => EvaluationDecision::RequestAdditionalEvidence,
            'rating' => null,
            'remarks' => fake()->sentence(),
        ]);
    }

    public function requestRevision(): static
    {
        return $this->state(fn (array $attributes): array => [
            'decision' => EvaluationDecision::RequestRevision,
            'rating' => null,
            'remarks' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'decision' => EvaluationDecision::Rejected,
            'rating' => null,
            'remarks' => fake()->sentence(),
        ]);
    }
}
