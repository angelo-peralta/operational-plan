<?php

namespace App\Actions\AcademicYears;

use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class CreateAcademicYear
{
    /**
     * Create an Academic Year with the institution's two reporting periods.
     *
     * @param  array{name: string, start_year: int, end_year: int, starts_on: string|null, ends_on: string|null, status: string, is_current: bool}  $data
     */
    public function handle(array $data): AcademicYear
    {
        return DB::transaction(function () use ($data): AcademicYear {
            if ($data['is_current']) {
                AcademicYear::query()
                    ->where('is_current', true)
                    ->lockForUpdate()
                    ->get();

                AcademicYear::query()
                    ->where('is_current', true)
                    ->update(['is_current' => false]);
            }

            $academicYear = AcademicYear::query()->create($data);

            $academicYear->reportingPeriods()->createMany([
                [
                    'name' => 'First Semester',
                    'code' => 'first_semester',
                    'sequence' => 1,
                    'status' => ReportingPeriodStatus::Draft,
                ],
                [
                    'name' => 'Second Semester',
                    'code' => 'second_semester',
                    'sequence' => 2,
                    'status' => ReportingPeriodStatus::Draft,
                ],
            ]);

            return $academicYear->load('reportingPeriods');
        });
    }
}
