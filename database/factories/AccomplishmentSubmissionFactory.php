<?php

namespace Database\Factories;

use App\Enums\AccomplishmentStatus;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AccomplishmentSubmission>
 */
class AccomplishmentSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'accomplishment_id' => Accomplishment::factory(),
            'revision' => 1,
            'submitted_by' => User::factory()->departmentUser(),
            'from_status' => AccomplishmentStatus::Draft->value,
            'to_status' => AccomplishmentStatus::UnderReview->value,
            'evidence_count' => 1,
            'snapshot' => [],
            'submitted_at' => now(),
        ];
    }
}
