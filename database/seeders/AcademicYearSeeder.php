<?php

namespace Database\Seeders;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hasCurrentAcademicYear = AcademicYear::query()
            ->where('is_current', true)
            ->exists();

        AcademicYear::query()->firstOrCreate(
            [
                'start_year' => 2025,
                'end_year' => 2026,
            ],
            [
                'name' => 'AY 2025-2026',
                'starts_on' => '2025-06-01',
                'ends_on' => '2026-05-31',
                'status' => AcademicYearStatus::Open,
                'is_current' => ! $hasCurrentAcademicYear,
            ],
        );

        AcademicYear::query()->firstOrCreate(
            [
                'start_year' => 2026,
                'end_year' => 2027,
            ],
            [
                'name' => 'AY 2026-2027',
                'starts_on' => '2026-06-01',
                'ends_on' => '2027-05-31',
                'status' => AcademicYearStatus::Draft,
                'is_current' => false,
            ],
        );
    }
}
