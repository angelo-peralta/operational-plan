<?php

namespace App\Http\Controllers;

use App\Data\ReportsData;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\OperationalPlan;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __invoke(
        string $currentTeam,
        Request $request,
        ReportsData $reports,
    ): Response {
        Gate::authorize('viewAny', OperationalPlan::class);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);
        $user = $request->user();

        if (! $academicYear instanceof AcademicYear || ! $user instanceof User) {
            return Inertia::render('reports/index', ['report' => null]);
        }

        return Inertia::render('reports/index', [
            'report' => $reports->forUser(
                $academicYear,
                $user,
                $this->selectedReportingPeriod($request, $academicYear),
            ),
        ]);
    }

    private function selectedReportingPeriod(
        Request $request,
        AcademicYear $academicYear,
    ): ?ReportingPeriod {
        if (! $request->query->has('reporting_period')) {
            return null;
        }

        $reportingPeriodId = $request->query('reporting_period');
        $isPositiveInteger = is_string($reportingPeriodId)
            && ctype_digit($reportingPeriodId)
            && (int) $reportingPeriodId > 0;

        abort_unless($isPositiveInteger, 404);

        return ReportingPeriod::query()
            ->whereBelongsTo($academicYear)
            ->findOrFail((int) $reportingPeriodId);
    }
}
