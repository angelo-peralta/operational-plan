<?php

namespace App\Http\Controllers;

use App\Actions\ReportingPeriods\UpdateReportingPeriod;
use App\Http\Requests\UpdateReportingPeriodRequest;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ReportingPeriodController extends Controller
{
    public function update(
        UpdateReportingPeriodRequest $request,
        string $currentTeam,
        AcademicYear $academicYear,
        ReportingPeriod $reportingPeriod,
        UpdateReportingPeriod $updateReportingPeriod,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $updateReportingPeriod->handle(
            $academicYear,
            $reportingPeriod,
            $actor,
            $request->validated(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reporting period updated.')]);

        return back();
    }
}
