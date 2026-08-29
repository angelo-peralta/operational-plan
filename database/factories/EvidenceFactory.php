<?php

namespace Database\Factories;

use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Evidence> */
class EvidenceFactory extends Factory
{
    public function definition(): array
    {
        $filename = Str::uuid().'.pdf';

        return [
            'accomplishment_id' => Accomplishment::factory(),
            'uploaded_by' => User::factory()->departmentUser(),
            'evidence_type' => 'report',
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'original_filename' => $filename,
            'stored_path' => 'evidence/testing/'.$filename,
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
            'checksum' => hash('sha256', $filename),
        ];
    }
}
