<?php

namespace App\Data;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationRating;
use App\Enums\OperationalPlanStatus;
use App\Enums\RemarkStatus;
use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportsData
{
    public function __construct(
        private ResolveOrganizationalScope $scope,
        private EffectiveEvaluationQuery $effectiveEvaluations,
        private RemarkReportData $remarkReport,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(
        AcademicYear $academicYear,
        User $user,
        ?ReportingPeriod $reportingPeriod = null,
    ): array {
        $unitIds = $this->scope->oversightUnitIds($user);
        $reportingPeriods = ReportingPeriod::query()
            ->whereBelongsTo($academicYear)
            ->orderBy('sequence')
            ->get();
        $units = OrganizationalUnit::query()
            ->whereKey($unitIds)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $plans = OperationalPlan::query()
            ->withCount(['keyResultAreas', 'planItems'])
            ->whereBelongsTo($academicYear)
            ->whereIn('organizational_unit_id', $unitIds)
            ->get()
            ->keyBy('organizational_unit_id');

        $accomplishments = $this->accomplishmentMetrics($academicYear, $unitIds, $reportingPeriod);
        $remarks = $this->remarkMetrics($academicYear, $unitIds, $reportingPeriod);
        $evaluations = $this->evaluationMetrics($academicYear, $unitIds, $reportingPeriod);
        $ratings = $this->ratingMetrics($academicYear, $unitIds, $reportingPeriod);
        $periodIds = $reportingPeriod instanceof ReportingPeriod
            ? ($reportingPeriod->status === ReportingPeriodStatus::Draft ? [] : [$reportingPeriod->id])
            : $reportingPeriods
                ->reject(fn (ReportingPeriod $period): bool => $period->status === ReportingPeriodStatus::Draft)
                ->pluck('id')
                ->map(fn (mixed $id): int => (int) $id)
                ->values()
                ->all();
        $evidenceCounts = $this->evidenceObligationCounts($academicYear, $unitIds, $periodIds);

        $unitRows = $units->map(function (OrganizationalUnit $unit) use (
            $plans,
            $accomplishments,
            $remarks,
            $evaluations,
            $ratings,
            $evidenceCounts,
            $periodIds,
        ): array {
            $plan = $plans->get($unit->id);
            $planItemCount = $plan !== null
                ? (int) $plan->getAttribute('plan_items_count')
                : 0;
            $requiredEvidenceCount = $plan !== null
                && in_array($plan->status, [OperationalPlanStatus::Approved, OperationalPlanStatus::Closed], true)
                    ? $planItemCount * count($periodIds)
                    : 0;

            return [
                'id' => $unit->id,
                'name' => $unit->name,
                'code' => $unit->code,
                'type' => $unit->type->value,
                'isActive' => $unit->is_active,
                'plan' => $plan !== null ? [
                    'id' => $plan->id,
                    'status' => $plan->status->value,
                    'statusLabel' => $plan->status->label(),
                    'keyResultAreaCount' => (int) $plan->getAttribute('key_result_areas_count'),
                    'planItemCount' => $planItemCount,
                ] : null,
                'accomplishments' => $accomplishments->get($unit->id, $this->emptyAccomplishments()),
                'pendingEvidence' => max(0, $requiredEvidenceCount - (int) $evidenceCounts->get($unit->id, 0)),
                'remarks' => $remarks->get($unit->id, $this->emptyRemarks()),
                'evaluations' => $evaluations[$unit->id] ?? $this->emptyEvaluations(),
                'ratings' => $ratings->get($unit->id, $this->emptyRatings()),
            ];
        })->values();

        return [
            'academicYear' => $this->academicYear($academicYear),
            'mode' => $reportingPeriod instanceof ReportingPeriod ? 'monitoring' : 'annual',
            'reportingPeriods' => $reportingPeriods
                ->map(fn (ReportingPeriod $period): array => $this->reportingPeriod($period))
                ->values(),
            'selectedReportingPeriod' => $reportingPeriod instanceof ReportingPeriod
                ? $this->reportingPeriod($reportingPeriod)
                : null,
            'summary' => $this->summary($unitRows->all(), $plans->all()),
            'organizationalUnits' => $unitRows,
            'remarks' => $this->remarkReport->forScope($academicYear, $unitIds, $reportingPeriod),
        ];
    }

    /** @param list<int> $unitIds
     * @return Collection<int, array{total: int, awaitingReview: int, returned: int, accepted: int, rejected: int}>
     */
    private function accomplishmentMetrics(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod,
    ): Collection {
        return $this->accomplishments($academicYear, $unitIds, $reportingPeriod)
            ->select('operational_plans.organizational_unit_id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS awaiting_review', [AccomplishmentStatus::UnderReview->value])
            ->selectRaw('SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS returned', [AccomplishmentStatus::Returned->value])
            ->selectRaw('SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS accepted', [AccomplishmentStatus::Accepted->value])
            ->selectRaw('SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS rejected', [AccomplishmentStatus::Rejected->value])
            ->groupBy('operational_plans.organizational_unit_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) data_get($row, 'organizational_unit_id') => [
                'total' => (int) data_get($row, 'total'),
                'awaitingReview' => (int) data_get($row, 'awaiting_review'),
                'returned' => (int) data_get($row, 'returned'),
                'accepted' => (int) data_get($row, 'accepted'),
                'rejected' => (int) data_get($row, 'rejected'),
            ]]);
    }

    /** @param list<int> $unitIds
     * @return Collection<int, array{total: int, open: int, addressed: int, resolved: int}>
     */
    private function remarkMetrics(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod,
    ): Collection {
        return DB::table('remarks')
            ->join('operational_plans', 'operational_plans.id', '=', 'remarks.operational_plan_id')
            ->where('remarks.academic_year_id', $academicYear->id)
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->when($reportingPeriod, fn (Builder $query) => $query->where(function (Builder $query) use ($reportingPeriod): void {
                $query->whereNull('remarks.reporting_period_id')
                    ->orWhere('remarks.reporting_period_id', $reportingPeriod->id);
            }))
            ->select('operational_plans.organizational_unit_id')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS open', [RemarkStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS addressed', [RemarkStatus::Addressed->value])
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS resolved', [RemarkStatus::Resolved->value])
            ->groupBy('operational_plans.organizational_unit_id')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) data_get($row, 'organizational_unit_id') => [
                'total' => (int) data_get($row, 'total'),
                'open' => (int) data_get($row, 'open'),
                'addressed' => (int) data_get($row, 'addressed'),
                'resolved' => (int) data_get($row, 'resolved'),
            ]]);
    }

    /** @param list<int> $unitIds
     * @return array<int, array{currentSubmissions: int, evaluated: int, awaitingReview: int, historyCount: int, completionRate: float|null}>
     */
    private function evaluationMetrics(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod,
    ): array {
        $latestSubmissionRevisions = DB::table('accomplishment_submissions')
            ->select('accomplishment_id')
            ->selectRaw('MAX(revision) AS revision')
            ->groupBy('accomplishment_id');
        $current = DB::table('accomplishment_submissions as current_submissions')
            ->joinSub($latestSubmissionRevisions, 'latest_submission_revisions', function ($join): void {
                $join->on('latest_submission_revisions.accomplishment_id', '=', 'current_submissions.accomplishment_id')
                    ->on('latest_submission_revisions.revision', '=', 'current_submissions.revision');
            })
            ->join('accomplishments', 'accomplishments.id', '=', 'current_submissions.accomplishment_id')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->leftJoin('evaluations', 'evaluations.accomplishment_submission_id', '=', 'current_submissions.id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->when($reportingPeriod, fn (Builder $query) => $query->where('reporting_periods.id', $reportingPeriod->id))
            ->select('operational_plans.organizational_unit_id')
            ->selectRaw('COUNT(DISTINCT current_submissions.id) AS current_submissions')
            ->selectRaw('COUNT(DISTINCT CASE WHEN evaluations.id IS NOT NULL THEN current_submissions.id END) AS evaluated')
            ->selectRaw('COUNT(DISTINCT CASE WHEN accomplishments.status = ? THEN current_submissions.id END) AS awaiting_review', [AccomplishmentStatus::UnderReview->value])
            ->groupBy('operational_plans.organizational_unit_id')
            ->get()
            ->keyBy(fn (object $row): int => (int) data_get($row, 'organizational_unit_id'));
        $history = DB::table('evaluations')
            ->join('accomplishment_submissions', 'accomplishment_submissions.id', '=', 'evaluations.accomplishment_submission_id')
            ->join('accomplishments', 'accomplishments.id', '=', 'accomplishment_submissions.accomplishment_id')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->when($reportingPeriod, fn (Builder $query) => $query->where('reporting_periods.id', $reportingPeriod->id))
            ->select('operational_plans.organizational_unit_id')
            ->selectRaw('COUNT(evaluations.id) AS aggregate')
            ->groupBy('operational_plans.organizational_unit_id')
            ->pluck('aggregate', 'operational_plans.organizational_unit_id');

        return $current->mapWithKeys(function (object $row, int $unitId) use ($history): array {
            $submissionCount = (int) data_get($row, 'current_submissions');
            $evaluatedCount = (int) data_get($row, 'evaluated');

            return [$unitId => [
                'currentSubmissions' => $submissionCount,
                'evaluated' => $evaluatedCount,
                'awaitingReview' => (int) data_get($row, 'awaiting_review'),
                'historyCount' => (int) $history->get($unitId, 0),
                'completionRate' => $submissionCount > 0
                    ? round(($evaluatedCount / $submissionCount) * 100, 1)
                    : null,
            ]];
        })->all();
    }

    /** @param list<int> $unitIds
     * @return Collection<int, array<string, mixed>>
     */
    private function ratingMetrics(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod,
    ): Collection {
        $rows = DB::table('evaluations as effective_evaluation_records')
            ->joinSub($this->effectiveEvaluations->current(), 'effective_evaluations', function ($join): void {
                $join->on('effective_evaluations.evaluation_id', '=', 'effective_evaluation_records.id');
            })
            ->join('accomplishments', 'accomplishments.id', '=', 'effective_evaluations.accomplishment_id')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->where('accomplishments.status', AccomplishmentStatus::Accepted->value)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->when($reportingPeriod, fn (Builder $query) => $query->where('reporting_periods.id', $reportingPeriod->id))
            ->select(['operational_plans.organizational_unit_id', 'effective_evaluation_records.rating'])
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('operational_plans.organizational_unit_id', 'effective_evaluation_records.rating')
            ->get()
            ->groupBy(fn (object $row): int => (int) data_get($row, 'organizational_unit_id'));

        return $rows->map(fn (Collection $unitRows): array => $this->ratingSummary(
            $unitRows->mapWithKeys(fn (object $row): array => [
                (int) data_get($row, 'rating') => (int) data_get($row, 'aggregate'),
            ]),
        ));
    }

    /** @param list<int> $unitIds
     * @param  array<int, int>  $periodIds
     * @return Collection<int, int>
     */
    private function evidenceObligationCounts(
        AcademicYear $academicYear,
        array $unitIds,
        array $periodIds,
    ): Collection {
        if ($periodIds === []) {
            return collect();
        }

        return DB::table('accomplishments')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->join('evidence', 'evidence.accomplishment_id', '=', 'accomplishments.id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->whereIn('operational_plans.status', [OperationalPlanStatus::Approved->value, OperationalPlanStatus::Closed->value])
            ->whereIn('reporting_periods.id', $periodIds)
            ->select('operational_plans.organizational_unit_id')
            ->selectRaw('COUNT(DISTINCT accomplishments.id) AS aggregate')
            ->groupBy('operational_plans.organizational_unit_id')
            ->pluck('aggregate', 'operational_plans.organizational_unit_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /** @param array<int, array<string, mixed>> $unitRowsData
     * @param  array<int, OperationalPlan>  $plansData
     * @return array<string, mixed>
     */
    private function summary(array $unitRowsData, array $plansData): array
    {
        $unitRows = collect($unitRowsData);
        $plans = collect($plansData);
        $planCounts = $plans->countBy(fn (OperationalPlan $plan): string => $plan->status->value);
        $ratingCounts = collect(EvaluationRating::cases())->mapWithKeys(function (EvaluationRating $rating) use ($unitRowsData): array {
            $count = 0;

            foreach ($unitRowsData as $unit) {
                foreach ((array) data_get($unit, 'ratings.distribution', []) as $distribution) {
                    if ((int) data_get($distribution, 'rating') === $rating->value) {
                        $count += (int) data_get($distribution, 'count');
                    }
                }
            }

            return [$rating->value => $count];
        });
        $activeUnits = $unitRows->where('isActive', true);

        return [
            'organizationalUnits' => [
                'active' => $activeUnits->count(),
                'withPlan' => $activeUnits->whereNotNull('plan')->count(),
                'withoutPlan' => $activeUnits->whereNull('plan')->count(),
            ],
            'plans' => [
                'total' => $plans->count(),
                'draft' => (int) $planCounts->get(OperationalPlanStatus::Draft->value, 0),
                'submitted' => (int) $planCounts->get(OperationalPlanStatus::Submitted->value, 0),
                'approved' => (int) $planCounts->get(OperationalPlanStatus::Approved->value, 0),
                'returned' => (int) $planCounts->get(OperationalPlanStatus::Returned->value, 0),
                'closed' => (int) $planCounts->get(OperationalPlanStatus::Closed->value, 0),
            ],
            'planItemCount' => (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, 'plan.planItemCount', 0)),
            'accomplishments' => $this->sumMetrics($unitRows, 'accomplishments', ['total', 'awaitingReview', 'returned', 'accepted', 'rejected']),
            'pendingEvidence' => (int) $unitRows->sum('pendingEvidence'),
            'remarks' => $this->sumMetrics($unitRows, 'remarks', ['total', 'open', 'addressed', 'resolved']),
            'evaluations' => $this->summaryEvaluations($unitRows),
            'ratings' => $this->ratingSummary($ratingCounts),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $unitRows
     * @param  list<string>  $keys
     * @return array<string, int>
     */
    private function sumMetrics(Collection $unitRows, string $group, array $keys): array
    {
        return collect($keys)->mapWithKeys(fn (string $key): array => [
            $key => (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, $group.'.'.$key, 0)),
        ])->all();
    }

    /** @param Collection<int, array<string, mixed>> $unitRows
     * @return array<string, float|int|null>
     */
    private function summaryEvaluations(Collection $unitRows): array
    {
        $currentSubmissions = (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, 'evaluations.currentSubmissions', 0));
        $evaluated = (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, 'evaluations.evaluated', 0));

        return [
            'currentSubmissions' => $currentSubmissions,
            'evaluated' => $evaluated,
            'awaitingReview' => (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, 'evaluations.awaitingReview', 0)),
            'historyCount' => (int) $unitRows->sum(fn (array $unit): int => (int) data_get($unit, 'evaluations.historyCount', 0)),
            'completionRate' => $currentSubmissions > 0
                ? round(($evaluated / $currentSubmissions) * 100, 1)
                : null,
        ];
    }

    /** @param Collection<int, int> $counts
     * @return array<string, mixed>
     */
    private function ratingSummary(Collection $counts): array
    {
        $ratedCount = (int) $counts->sum();
        $weightedTotal = (int) $counts->reduce(
            fn (int $total, int $count, int $rating): int => $total + ($rating * $count),
            0,
        );

        return [
            'ratedCount' => $ratedCount,
            'average' => $ratedCount > 0 ? round($weightedTotal / $ratedCount, 2) : null,
            'distribution' => collect(EvaluationRating::cases())->map(fn (EvaluationRating $rating): array => [
                'rating' => $rating->value,
                'label' => $rating->label(),
                'count' => (int) $counts->get($rating->value, 0),
            ])->all(),
        ];
    }

    /** @param list<int> $unitIds */
    private function accomplishments(
        AcademicYear $academicYear,
        array $unitIds,
        ?ReportingPeriod $reportingPeriod,
    ): Builder {
        return DB::table('accomplishments')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->when($reportingPeriod, fn (Builder $query) => $query->where('reporting_periods.id', $reportingPeriod->id));
    }

    /** @return array<string, int> */
    private function emptyAccomplishments(): array
    {
        return ['total' => 0, 'awaitingReview' => 0, 'returned' => 0, 'accepted' => 0, 'rejected' => 0];
    }

    /** @return array<string, int> */
    private function emptyRemarks(): array
    {
        return ['total' => 0, 'open' => 0, 'addressed' => 0, 'resolved' => 0];
    }

    /** @return array<string, float|int|null> */
    private function emptyEvaluations(): array
    {
        return ['currentSubmissions' => 0, 'evaluated' => 0, 'awaitingReview' => 0, 'historyCount' => 0, 'completionRate' => null];
    }

    /** @return array<string, mixed> */
    private function emptyRatings(): array
    {
        return $this->ratingSummary(collect([0 => 0, 1 => 0, 2 => 0, 3 => 0]));
    }

    /** @return array{id: int, name: string, status: string, isCurrent: bool, startYear: int, endYear: int} */
    private function academicYear(AcademicYear $academicYear): array
    {
        return [
            'id' => $academicYear->id,
            'name' => $academicYear->name,
            'status' => $academicYear->status->value,
            'isCurrent' => $academicYear->is_current,
            'startYear' => $academicYear->start_year,
            'endYear' => $academicYear->end_year,
        ];
    }

    /** @return array{id: int, name: string, code: string, sequence: int, status: string} */
    private function reportingPeriod(ReportingPeriod $reportingPeriod): array
    {
        return [
            'id' => $reportingPeriod->id,
            'name' => $reportingPeriod->name,
            'code' => $reportingPeriod->code,
            'sequence' => $reportingPeriod->sequence,
            'status' => $reportingPeriod->status->value,
        ];
    }
}
