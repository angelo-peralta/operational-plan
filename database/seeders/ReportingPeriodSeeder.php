<?php

namespace Database\Seeders;

use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReportingPeriodSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $reportingPeriods = [
            [
                'name' => 'First Semester',
                'code' => 'first_semester',
                'sequence' => 1,
            ],
            [
                'name' => 'Second Semester',
                'code' => 'second_semester',
                'sequence' => 2,
            ],
        ];

        AcademicYear::query()->each(function (AcademicYear $academicYear) use ($reportingPeriods): void {
            foreach ($reportingPeriods as $reportingPeriod) {
                $alreadySeeded = ReportingPeriod::query()
                    ->where('academic_year_id', $academicYear->id)
                    ->where(function ($query) use ($reportingPeriod): void {
                        $query
                            ->where('code', $reportingPeriod['code'])
                            ->orWhere('sequence', $reportingPeriod['sequence']);
                    })
                    ->exists();

                if ($alreadySeeded) {
                    continue;
                }

                ReportingPeriod::query()->create([
                    'academic_year_id' => $academicYear->id,
                    'name' => $reportingPeriod['name'],
                    'code' => $reportingPeriod['code'],
                    'sequence' => $reportingPeriod['sequence'],
                    'starts_on' => null,
                    'ends_on' => null,
                    'status' => ReportingPeriodStatus::Draft,
                ]);
            }
        });
    }
}
