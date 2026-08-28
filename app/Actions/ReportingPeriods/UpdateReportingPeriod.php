<?php

namespace App\Actions\ReportingPeriods;

use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateReportingPeriod
{
    /** @param array<string, mixed> $data */
    public function handle(
        AcademicYear $academicYear,
        ReportingPeriod $reportingPeriod,
        User $actor,
        array $data,
    ): ReportingPeriod {
        return DB::transaction(function () use ($academicYear, $reportingPeriod, $actor, $data): ReportingPeriod {
            $lockedAcademicYear = AcademicYear::query()
                ->whereKey($academicYear->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedReportingPeriod = ReportingPeriod::query()
                ->whereKey($reportingPeriod->id)
                ->whereBelongsTo($lockedAcademicYear)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedReportingPeriod->setRelation('academicYear', $lockedAcademicYear);

            Gate::forUser($actor)->authorize('update', $lockedReportingPeriod);

            $lockedReportingPeriod->update($data);

            return $lockedReportingPeriod;
        });
    }
}
