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

final class DashboardData
{
    public function __construct(
        private ResolveOrganizationalScope $scope,
        private EffectiveEvaluationQuery $effectiveEvaluations,
    ) {}

    /** @return array<string, mixed>|null */
    public function forUser(?AcademicYear $academicYear, User $user): ?array
    {
        if (! $academicYear instanceof AcademicYear) {
            return null;
        }

        $unitIds = $this->scope->visibleUnitIds($user);
        if ($user->isStaff() || $user->isOfficeHead()) {
            $units = OrganizationalUnit::query()->whereKey($unitIds)->orderBy('name')->get();
            if ($units->count() <= 1) {
                return $this->unitSummary($academicYear, $units->first());
            }

            return [
                'kind' => 'units',
                'units' => $units->map(fn (OrganizationalUnit $unit): array => $this->unitSummary($academicYear, $unit))->all(),
            ];
        }

        return $this->oversightSummary($academicYear, $this->scope->oversightUnitIds($user));
    }

    /** @return array<string, mixed> */
    private function unitSummary(AcademicYear $academicYear, ?OrganizationalUnit $organizationalUnit): array
    {
        if (! $organizationalUnit instanceof OrganizationalUnit) {
            return [
                'kind' => 'unit',
                'organizationalUnit' => null,
                'plan' => null,
                'reportingPeriods' => [],
                'pendingEvidence' => 0,
                'remarks' => $this->emptyRemarkSummary(),
                'accomplishments' => $this->emptyAccomplishmentSummary(),
                'evaluations' => $this->emptyEvaluationSummary(),
                'ratings' => $this->emptyRatingSummary(),
            ];
        }

        $reportingPeriods = ReportingPeriod::query()
            ->whereBelongsTo($academicYear)
            ->select(['id', 'academic_year_id', 'name', 'code', 'sequence', 'status'])
            ->orderBy('sequence')
            ->get();
        $operationalPlan = OperationalPlan::query()
            ->whereBelongsTo($academicYear)
            ->where('organizational_unit_id', $organizationalUnit->id)
            ->select(['id', 'academic_year_id', 'organizational_unit_id', 'status'])
            ->first();

        if (! $operationalPlan instanceof OperationalPlan) {
            return [
                'kind' => 'unit',
                'organizationalUnit' => $this->organizationalUnit($organizationalUnit),
                'plan' => null,
                'reportingPeriods' => $reportingPeriods
                    ->map(fn (ReportingPeriod $reportingPeriod): array => $this->emptyReportingPeriod($reportingPeriod))
                    ->values()
                    ->all(),
                'pendingEvidence' => 0,
                'remarks' => $this->emptyRemarkSummary(),
                'accomplishments' => $this->emptyAccomplishmentSummary(),
                'evaluations' => $this->emptyEvaluationSummary(),
                'ratings' => $this->emptyRatingSummary(),
            ];
        }

        $keyResultAreaCount = DB::table('key_result_areas')
            ->where('operational_plan_id', $operationalPlan->id)
            ->count();
        $planItemCount = DB::table('plan_items')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->where('key_result_areas.operational_plan_id', $operationalPlan->id)
            ->count();
        $statusCounts = $this->departmentAccomplishmentStatusCounts($academicYear, $operationalPlan);
        $submissionCounts = $this->departmentSubmissionCounts($academicYear, $operationalPlan);

        $periodSummaries = $reportingPeriods
            ->map(function (ReportingPeriod $reportingPeriod) use ($statusCounts, $submissionCounts): array {
                $periodStatusCounts = $statusCounts->get($reportingPeriod->id);

                return [
                    'id' => $reportingPeriod->id,
                    'name' => $reportingPeriod->name,
                    'code' => $reportingPeriod->code,
                    'status' => $reportingPeriod->status->value,
                    'submitted' => (int) ($submissionCounts->get($reportingPeriod->id) ?? 0),
                    'awaitingReview' => (int) data_get($periodStatusCounts, 'awaiting_review', 0),
                    'returned' => (int) data_get($periodStatusCounts, 'returned', 0),
                    'accepted' => (int) data_get($periodStatusCounts, 'accepted', 0),
                    'rejected' => (int) data_get($periodStatusCounts, 'rejected', 0),
                ];
            })
            ->values();

        return [
            'kind' => 'unit',
            'organizationalUnit' => $this->organizationalUnit($organizationalUnit),
            'plan' => [
                'id' => $operationalPlan->id,
                'status' => $operationalPlan->status->value,
                'statusLabel' => $operationalPlan->status->label(),
                'keyResultAreaCount' => $keyResultAreaCount,
                'planItemCount' => $planItemCount,
            ],
            'reportingPeriods' => $periodSummaries->all(),
            'pendingEvidence' => $this->pendingEvidenceCount(
                $operationalPlan,
                $planItemCount,
                $reportingPeriods,
            ),
            'remarks' => $this->remarkSummary($academicYear, [$organizationalUnit->id]),
            'accomplishments' => [
                'awaitingReview' => (int) $periodSummaries->sum('awaitingReview'),
                'returned' => (int) $periodSummaries->sum('returned'),
                'accepted' => (int) $periodSummaries->sum('accepted'),
                'rejected' => (int) $periodSummaries->sum('rejected'),
            ],
            'evaluations' => $this->evaluationSummary($academicYear, [$organizationalUnit->id]),
            'ratings' => $this->ratingSummary($academicYear, [$organizationalUnit->id]),
        ];
    }

    /** @param list<int> $unitIds
     * @return array<string, mixed>
     */
    private function oversightSummary(AcademicYear $academicYear, array $unitIds): array
    {
        $activeOrganizationalUnits = OrganizationalUnit::query()
            ->whereKey($unitIds)
            ->where('is_active', true);
        $activeOrganizationalUnitCount = (clone $activeOrganizationalUnits)->count();
        $activeOrganizationalUnitIds = (clone $activeOrganizationalUnits)->select('id');

        $activeOrganizationalUnitsWithPlanCount = DB::table('operational_plans')
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('organizational_unit_id', $activeOrganizationalUnitIds)
            ->count();

        $planCounts = DB::table('operational_plans')
            ->whereIn('organizational_unit_id', $unitIds)
            ->where('academic_year_id', $academicYear->id)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS draft', [OperationalPlanStatus::Draft->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS submitted', [OperationalPlanStatus::Submitted->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS approved', [OperationalPlanStatus::Approved->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS returned', [OperationalPlanStatus::Returned->value])
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) AS closed', [OperationalPlanStatus::Closed->value])
            ->first();

        $accomplishmentCounts = $this->accomplishmentsForAcademicYear($academicYear)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->whereIn('accomplishments.status', [
                AccomplishmentStatus::UnderReview->value,
                AccomplishmentStatus::Returned->value,
                AccomplishmentStatus::Accepted->value,
                AccomplishmentStatus::Rejected->value,
            ])
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS awaiting_review',
                [AccomplishmentStatus::UnderReview->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS returned',
                [AccomplishmentStatus::Returned->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS accepted',
                [AccomplishmentStatus::Accepted->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS rejected',
                [AccomplishmentStatus::Rejected->value],
            )
            ->first();

        $planCount = (int) data_get($planCounts, 'total', 0);

        return [
            'kind' => 'oversight',
            'organizationalUnits' => [
                'active' => $activeOrganizationalUnitCount,
                'withPlan' => $activeOrganizationalUnitsWithPlanCount,
                'withoutPlan' => max(0, $activeOrganizationalUnitCount - $activeOrganizationalUnitsWithPlanCount),
            ],
            'plans' => [
                'total' => $planCount,
                'draft' => (int) data_get($planCounts, 'draft', 0),
                'submitted' => (int) data_get($planCounts, 'submitted', 0),
                'approved' => (int) data_get($planCounts, 'approved', 0),
                'returned' => (int) data_get($planCounts, 'returned', 0),
                'closed' => (int) data_get($planCounts, 'closed', 0),
            ],
            'accomplishments' => [
                'total' => (int) data_get($accomplishmentCounts, 'total', 0),
                'awaitingReview' => (int) data_get($accomplishmentCounts, 'awaiting_review', 0),
                'returned' => (int) data_get($accomplishmentCounts, 'returned', 0),
                'accepted' => (int) data_get($accomplishmentCounts, 'accepted', 0),
                'rejected' => (int) data_get($accomplishmentCounts, 'rejected', 0),
            ],
            'remarks' => $this->remarkSummary($academicYear, $unitIds),
            'evaluations' => $this->evaluationSummary($academicYear, $unitIds),
            'ratings' => $this->ratingSummary($academicYear, $unitIds),
        ];
    }

    /** @return Collection<int, \stdClass> */
    private function departmentAccomplishmentStatusCounts(
        AcademicYear $academicYear,
        OperationalPlan $operationalPlan,
    ): Collection {
        return $this->accomplishmentsForAcademicYear($academicYear)
            ->where('key_result_areas.operational_plan_id', $operationalPlan->id)
            ->select('accomplishments.reporting_period_id')
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS awaiting_review',
                [AccomplishmentStatus::UnderReview->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS returned',
                [AccomplishmentStatus::Returned->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS accepted',
                [AccomplishmentStatus::Accepted->value],
            )
            ->selectRaw(
                'SUM(CASE WHEN accomplishments.status = ? THEN 1 ELSE 0 END) AS rejected',
                [AccomplishmentStatus::Rejected->value],
            )
            ->groupBy('accomplishments.reporting_period_id')
            ->get()
            ->mapWithKeys(fn (\stdClass $row): array => [
                (int) data_get($row, 'reporting_period_id') => $row,
            ]);
    }

    /** @return Collection<int, int> */
    private function departmentSubmissionCounts(
        AcademicYear $academicYear,
        OperationalPlan $operationalPlan,
    ): Collection {
        return $this->accomplishmentsForAcademicYear($academicYear)
            ->where('key_result_areas.operational_plan_id', $operationalPlan->id)
            ->whereNotNull('accomplishments.submitted_at')
            ->select('accomplishments.reporting_period_id')
            ->selectRaw('COUNT(DISTINCT accomplishments.id) AS submitted')
            ->groupBy('accomplishments.reporting_period_id')
            ->pluck('submitted', 'accomplishments.reporting_period_id')
            ->map(fn (mixed $count): int => (int) $count);
    }

    /** @param Collection<int, ReportingPeriod> $reportingPeriods */
    private function pendingEvidenceCount(
        OperationalPlan $operationalPlan,
        int $planItemCount,
        Collection $reportingPeriods,
    ): int {
        if (! in_array($operationalPlan->status, [OperationalPlanStatus::Approved, OperationalPlanStatus::Closed], true)) {
            return 0;
        }

        $monitoredPeriodIds = $reportingPeriods
            ->reject(fn (ReportingPeriod $reportingPeriod): bool => $reportingPeriod->status === ReportingPeriodStatus::Draft)
            ->pluck('id');

        if ($planItemCount === 0 || $monitoredPeriodIds->isEmpty()) {
            return 0;
        }

        $obligationsWithEvidence = DB::table('accomplishments')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('evidence', 'evidence.accomplishment_id', '=', 'accomplishments.id')
            ->where('key_result_areas.operational_plan_id', $operationalPlan->id)
            ->whereIn('accomplishments.reporting_period_id', $monitoredPeriodIds)
            ->distinct()
            ->count('accomplishments.id');

        return max(0, ($planItemCount * $monitoredPeriodIds->count()) - $obligationsWithEvidence);
    }

    /** @param list<int> $unitIds
     * @return array<string, mixed>
     */
    private function ratingSummary(
        AcademicYear $academicYear,
        array $unitIds,
    ): array {
        $query = DB::table('evaluations as effective_evaluation_records')
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
            ->where('accomplishments.status', AccomplishmentStatus::Accepted->value);

        $query->whereIn('operational_plans.organizational_unit_id', $unitIds);

        $counts = $query
            ->select('effective_evaluation_records.rating')
            ->selectRaw('COUNT(*) AS aggregate')
            ->groupBy('effective_evaluation_records.rating')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(int) data_get($row, 'rating') => (int) data_get($row, 'aggregate')]);
        $ratedCount = (int) $counts->sum();
        $weightedTotal = (int) $counts->reduce(
            fn (int $total, int $count, int $rating): int => $total + ($rating * $count),
            0,
        );

        return [
            'ratedCount' => $ratedCount,
            'average' => $ratedCount > 0 ? (float) round($weightedTotal / $ratedCount, 2) : null,
            'distribution' => collect([
                EvaluationRating::Exceeded,
                EvaluationRating::Met,
                EvaluationRating::SomehowMet,
                EvaluationRating::DidNotMeet,
            ])->map(fn (EvaluationRating $rating): array => [
                'rating' => $rating->value,
                'label' => $rating->label(),
                'count' => (int) ($counts->get($rating->value) ?? 0),
            ])->all(),
        ];
    }

    /** @param list<int> $unitIds
     * @return array{total: int, open: int, addressed: int, resolved: int}
     */
    private function remarkSummary(AcademicYear $academicYear, array $unitIds): array
    {
        $counts = DB::table('remarks')
            ->join('operational_plans', 'operational_plans.id', '=', 'remarks.operational_plan_id')
            ->where('remarks.academic_year_id', $academicYear->id)
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS open', [RemarkStatus::Open->value])
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS addressed', [RemarkStatus::Addressed->value])
            ->selectRaw('SUM(CASE WHEN remarks.status = ? THEN 1 ELSE 0 END) AS resolved', [RemarkStatus::Resolved->value])
            ->first();

        return [
            'total' => (int) data_get($counts, 'total', 0),
            'open' => (int) data_get($counts, 'open', 0),
            'addressed' => (int) data_get($counts, 'addressed', 0),
            'resolved' => (int) data_get($counts, 'resolved', 0),
        ];
    }

    /** @param list<int> $unitIds
     * @return array{currentSubmissions: int, evaluated: int, awaitingReview: int, historyCount: int, completionRate: float|null}
     */
    private function evaluationSummary(AcademicYear $academicYear, array $unitIds): array
    {
        $latestSubmissionRevisions = DB::table('accomplishment_submissions')
            ->select('accomplishment_id')
            ->selectRaw('MAX(revision) AS revision')
            ->groupBy('accomplishment_id');
        $currentSubmissions = DB::table('accomplishment_submissions as current_submissions')
            ->joinSub($latestSubmissionRevisions, 'latest_submission_revisions', function ($join): void {
                $join->on('latest_submission_revisions.accomplishment_id', '=', 'current_submissions.accomplishment_id')
                    ->on('latest_submission_revisions.revision', '=', 'current_submissions.revision');
            })
            ->join('accomplishments', 'accomplishments.id', '=', 'current_submissions.accomplishment_id')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds);

        $currentSubmissionCount = (clone $currentSubmissions)->count('current_submissions.id');
        $evaluatedCount = (clone $currentSubmissions)
            ->whereExists(function (Builder $query): void {
                $query->selectRaw('1')
                    ->from('evaluations')
                    ->whereColumn('evaluations.accomplishment_submission_id', 'current_submissions.id');
            })
            ->count('current_submissions.id');
        $awaitingReviewCount = (clone $currentSubmissions)
            ->where('accomplishments.status', AccomplishmentStatus::UnderReview->value)
            ->count('current_submissions.id');
        $historyCount = DB::table('evaluations')
            ->join('accomplishment_submissions', 'accomplishment_submissions.id', '=', 'evaluations.accomplishment_submission_id')
            ->join('accomplishments', 'accomplishments.id', '=', 'accomplishment_submissions.accomplishment_id')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id)
            ->whereIn('operational_plans.organizational_unit_id', $unitIds)
            ->count('evaluations.id');

        return [
            'currentSubmissions' => $currentSubmissionCount,
            'evaluated' => $evaluatedCount,
            'awaitingReview' => $awaitingReviewCount,
            'historyCount' => $historyCount,
            'completionRate' => $currentSubmissionCount > 0
                ? round(($evaluatedCount / $currentSubmissionCount) * 100, 1)
                : null,
        ];
    }

    private function accomplishmentsForAcademicYear(AcademicYear $academicYear): Builder
    {
        return DB::table('accomplishments')
            ->join('plan_items', 'plan_items.id', '=', 'accomplishments.plan_item_id')
            ->join('key_result_areas', 'key_result_areas.id', '=', 'plan_items.key_result_area_id')
            ->join('operational_plans', 'operational_plans.id', '=', 'key_result_areas.operational_plan_id')
            ->join('reporting_periods', 'reporting_periods.id', '=', 'accomplishments.reporting_period_id')
            ->where('operational_plans.academic_year_id', $academicYear->id)
            ->where('reporting_periods.academic_year_id', $academicYear->id);
    }

    /** @return array{id: int, name: string, code: string|null} */
    private function organizationalUnit(OrganizationalUnit $organizationalUnit): array
    {
        return [
            'id' => $organizationalUnit->id,
            'name' => $organizationalUnit->name,
            'code' => $organizationalUnit->code,
        ];
    }

    /** @return array<string, int> */
    private function emptyAccomplishmentSummary(): array
    {
        return [
            'awaitingReview' => 0,
            'returned' => 0,
            'accepted' => 0,
            'rejected' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function emptyRatingSummary(): array
    {
        return [
            'ratedCount' => 0,
            'average' => null,
            'distribution' => collect([
                EvaluationRating::Exceeded,
                EvaluationRating::Met,
                EvaluationRating::SomehowMet,
                EvaluationRating::DidNotMeet,
            ])->map(fn (EvaluationRating $rating): array => [
                'rating' => $rating->value,
                'label' => $rating->label(),
                'count' => 0,
            ])->all(),
        ];
    }

    /** @return array{total: int, open: int, addressed: int, resolved: int} */
    private function emptyRemarkSummary(): array
    {
        return ['total' => 0, 'open' => 0, 'addressed' => 0, 'resolved' => 0];
    }

    /** @return array{currentSubmissions: int, evaluated: int, awaitingReview: int, historyCount: int, completionRate: null} */
    private function emptyEvaluationSummary(): array
    {
        return [
            'currentSubmissions' => 0,
            'evaluated' => 0,
            'awaitingReview' => 0,
            'historyCount' => 0,
            'completionRate' => null,
        ];
    }

    /** @return array<string, int|string> */
    private function emptyReportingPeriod(ReportingPeriod $reportingPeriod): array
    {
        return [
            'id' => $reportingPeriod->id,
            'name' => $reportingPeriod->name,
            'code' => $reportingPeriod->code,
            'status' => $reportingPeriod->status->value,
            'submitted' => 0,
            'awaitingReview' => 0,
            'returned' => 0,
            'accepted' => 0,
            'rejected' => 0,
        ];
    }
}
