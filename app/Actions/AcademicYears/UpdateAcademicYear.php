<?php

namespace App\Actions\AcademicYears;

use App\Models\AcademicYear;
use Illuminate\Support\Facades\DB;

class UpdateAcademicYear
{
    /**
     * Update an Academic Year while preserving the single-current invariant.
     *
     * @param  array{name: string, start_year: int, end_year: int, starts_on: string|null, ends_on: string|null, status: string, is_current: bool}  $data
     */
    public function handle(AcademicYear $academicYear, array $data): AcademicYear
    {
        return DB::transaction(function () use ($academicYear, $data): AcademicYear {
            $lockedAcademicYear = AcademicYear::query()
                ->whereKey($academicYear->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($data['is_current']) {
                AcademicYear::query()
                    ->where('is_current', true)
                    ->whereKeyNot($lockedAcademicYear->getKey())
                    ->lockForUpdate()
                    ->get();

                AcademicYear::query()
                    ->where('is_current', true)
                    ->whereKeyNot($lockedAcademicYear->getKey())
                    ->update(['is_current' => false]);
            }

            $lockedAcademicYear->update($data);

            return $lockedAcademicYear->refresh()->load('reportingPeriods');
        });
    }
}
