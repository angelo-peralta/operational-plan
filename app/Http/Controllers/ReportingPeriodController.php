<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateReportingPeriodRequest;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class ReportingPeriodController extends Controller
{
    public function update(
        UpdateReportingPeriodRequest $request,
        string $currentTeam,
        AcademicYear $academicYear,
        ReportingPeriod $reportingPeriod,
    ): RedirectResponse {
        $reportingPeriod->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Reporting period updated.')]);

        return back();
    }
}
