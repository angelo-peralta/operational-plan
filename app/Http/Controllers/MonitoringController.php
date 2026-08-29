<?php

namespace App\Http\Controllers;

use App\Data\MonitoringData;
use App\Enums\OperationalPlanStatus;
use App\Enums\ReportingPeriodStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\OperationalPlan;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function __invoke(string $currentTeam, Request $request): Response
    {
        Gate::authorize('viewAny', Accomplishment::class);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);
        $user = $request->user();

        if (! $academicYear instanceof AcademicYear || ! $user instanceof User) {
            return Inertia::render('monitoring/index', [
                'academicYear' => null,
                'reportingPeriods' => [],
                'selectedReportingPeriod' => null,
                'operationalPlans' => [],
            ]);
        }

        $reportingPeriods = ReportingPeriod::query()
            ->whereBelongsTo($academicYear)
            ->orderBy('sequence')
            ->get()
            ->each(fn (ReportingPeriod $reportingPeriod) => $reportingPeriod->setRelation('academicYear', $academicYear));
        $selectedReportingPeriod = $this->selectedReportingPeriod($request, $reportingPeriods);

        return Inertia::render('monitoring/index', [
            'academicYear' => MonitoringData::academicYear($academicYear),
            'reportingPeriods' => $reportingPeriods
                ->map(fn (ReportingPeriod $reportingPeriod): array => MonitoringData::reportingPeriod($reportingPeriod))
                ->values(),
            'selectedReportingPeriod' => $selectedReportingPeriod
                ? MonitoringData::reportingPeriod($selectedReportingPeriod)
                : null,
            'operationalPlans' => $selectedReportingPeriod
                ? $this->operationalPlans($academicYear, $selectedReportingPeriod, $user)
                : [],
        ]);
    }

    /** @param Collection<int, ReportingPeriod> $reportingPeriods */
    private function selectedReportingPeriod(Request $request, Collection $reportingPeriods): ?ReportingPeriod
    {
        if ($request->query->has('reporting_period')) {
            $reportingPeriodId = $request->query('reporting_period');
            $isPositiveInteger = is_string($reportingPeriodId)
                && ctype_digit($reportingPeriodId)
                && (int) $reportingPeriodId > 0;

            abort_unless($isPositiveInteger, 404);

            $reportingPeriod = $reportingPeriods->firstWhere('id', (int) $reportingPeriodId);
            abort_unless($reportingPeriod instanceof ReportingPeriod, 404);

            return $reportingPeriod;
        }

        return $reportingPeriods->first(
            fn (ReportingPeriod $reportingPeriod): bool => $reportingPeriod->status === ReportingPeriodStatus::Open,
        ) ?? $reportingPeriods->first();
    }

    /** @return list<array<string, mixed>> */
    private function operationalPlans(
        AcademicYear $academicYear,
        ReportingPeriod $reportingPeriod,
        User $user,
    ): array {
        $operationalPlans = OperationalPlan::query()
            ->with([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'department:id,name,code',
                'keyResultAreas:id,operational_plan_id,code,name,description,sort_order',
                'keyResultAreas.planItems:id,key_result_area_id,objective,strategy,kpi_target_text,target_value,target_unit,target_operator,target_frequency,documentary_evidence_requirements,sort_order',
                'keyResultAreas.planItems.accomplishments' => fn (Builder $query): Builder => $query
                    ->select([
                        'id',
                        'plan_item_id',
                        'reporting_period_id',
                        'reported_value',
                        'accomplishment_text',
                        'percentage_accomplished',
                        'status',
                        'submitted_at',
                        'resubmitted_at',
                        'updated_at',
                    ])
                    ->whereBelongsTo($reportingPeriod)
                    ->with(['evidence' => fn (Builder $query): Builder => $query
                        ->select([
                            'id',
                            'accomplishment_id',
                            'evidence_type',
                            'title',
                            'description',
                            'original_filename',
                            'mime_type',
                            'file_size',
                            'uploaded_by',
                            'created_at',
                        ])
                        ->with('uploader:id,name')
                        ->oldest()]),
            ])
            ->whereBelongsTo($academicYear)
            ->whereIn('status', [
                OperationalPlanStatus::Approved,
                OperationalPlanStatus::Closed,
            ])
            ->when(
                $user->isDepartmentUser(),
                fn (Builder $query): Builder => $query->where('department_id', $user->department_id),
            )
            ->orderBy('department_id')
            ->get()
            ->map(fn (OperationalPlan $operationalPlan): array => MonitoringData::operationalPlan(
                $operationalPlan,
                $reportingPeriod,
                $user,
            ))
            ->all();

        return array_values($operationalPlans);
    }
}
