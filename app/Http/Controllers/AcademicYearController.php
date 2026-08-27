<?php

namespace App\Http\Controllers;

use App\Actions\AcademicYears\CreateAcademicYear;
use App\Actions\AcademicYears\UpdateAcademicYear;
use App\Enums\AcademicYearStatus;
use App\Enums\ReportingPeriodStatus;
use App\Http\Requests\StoreAcademicYearRequest;
use App\Http\Requests\UpdateAcademicYearRequest;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(string $currentTeam): Response
    {
        Gate::authorize('create', AcademicYear::class);

        $academicYears = AcademicYear::query()
            ->with('reportingPeriods')
            ->orderByDesc('start_year')
            ->get()
            ->map(fn (AcademicYear $academicYear): array => $this->toArray($academicYear));

        return Inertia::render('academic-years/index', [
            'academicYears' => $academicYears,
            'academicYearStatuses' => AcademicYearStatus::options(),
            'reportingPeriodStatuses' => ReportingPeriodStatus::options(),
        ]);
    }

    public function store(
        StoreAcademicYearRequest $request,
        string $currentTeam,
        CreateAcademicYear $createAcademicYear,
    ): RedirectResponse {
        $createAcademicYear->handle($request->validatedData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Academic Year created.')]);

        return back();
    }

    public function update(
        UpdateAcademicYearRequest $request,
        string $currentTeam,
        AcademicYear $academicYear,
        UpdateAcademicYear $updateAcademicYear,
    ): RedirectResponse {
        $updateAcademicYear->handle($academicYear, $request->validatedData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Academic Year updated.')]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(AcademicYear $academicYear): array
    {
        return [
            'id' => $academicYear->id,
            'name' => $academicYear->name,
            'startYear' => $academicYear->start_year,
            'endYear' => $academicYear->end_year,
            'startsOn' => $academicYear->starts_on?->toDateString(),
            'endsOn' => $academicYear->ends_on?->toDateString(),
            'status' => $academicYear->status->value,
            'isCurrent' => $academicYear->is_current,
            'reportingPeriods' => $academicYear->reportingPeriods->map(
                fn (ReportingPeriod $reportingPeriod): array => [
                    'id' => $reportingPeriod->id,
                    'name' => $reportingPeriod->name,
                    'code' => $reportingPeriod->code,
                    'sequence' => $reportingPeriod->sequence,
                    'startsOn' => $reportingPeriod->starts_on?->toDateString(),
                    'endsOn' => $reportingPeriod->ends_on?->toDateString(),
                    'status' => $reportingPeriod->status->value,
                ],
            )->values(),
        ];
    }
}
