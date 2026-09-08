<?php

namespace App\Http\Controllers;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Data\EvaluationData;
use App\Data\MonitoringData;
use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationRating;
use App\Enums\OperationalPlanStatus;
use App\Enums\ReportingPeriodStatus;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EvaluationQueueController extends Controller
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    /**
     * Display the selected-year manual review queue.
     */
    public function __invoke(string $currentTeam, Request $request): Response
    {
        Gate::authorize('viewAny', Evaluation::class);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);
        $user = $request->user();

        if (! $academicYear instanceof AcademicYear || ! $user instanceof User) {
            return $this->renderEmptyQueue();
        }

        $reportingPeriods = ReportingPeriod::query()
            ->whereBelongsTo($academicYear)
            ->orderBy('sequence')
            ->get()
            ->each(fn (ReportingPeriod $reportingPeriod) => $reportingPeriod->setRelation('academicYear', $academicYear));
        $selectedReportingPeriod = $this->selectedReportingPeriod($request, $reportingPeriods);

        return Inertia::render('evaluations/index', [
            'academicYear' => MonitoringData::academicYear($academicYear),
            'reportingPeriods' => $reportingPeriods
                ->map(fn (ReportingPeriod $reportingPeriod): array => MonitoringData::reportingPeriod($reportingPeriod))
                ->values(),
            'selectedReportingPeriod' => $selectedReportingPeriod
                ? MonitoringData::reportingPeriod($selectedReportingPeriod)
                : null,
            'queue' => $selectedReportingPeriod
                ? $this->queue($academicYear, $selectedReportingPeriod, $user)
                : [],
            'ratingOptions' => EvaluationRating::options(),
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
    private function queue(
        AcademicYear $academicYear,
        ReportingPeriod $reportingPeriod,
        User $user,
    ): array {
        $queue = Accomplishment::query()
            ->with([
                'planItem.keyResultArea.operationalPlan.academicYear',
                'reportingPeriod.academicYear',
                'remarks.author.organizationalUnitAssignments.organizationalUnit:id,type',
                'remarks.reportingPeriod:id,name',
                'evidence.remarks.author.organizationalUnitAssignments.organizationalUnit:id,type',
                'evidence.remarks.reportingPeriod:id,name',
                'submissions.submitter:id,name',
                'submissions.evaluations.reviewer:id,name',
                'submissions.evaluations.organizationalUnit:id,name,code,type',
                'submissions.evaluations.overriddenEvaluation:id,reviewer_id,rating',
                'submissions.evaluations.fieldRemarks.author.organizationalUnitAssignments.organizationalUnit:id,type',
                'submissions.evaluations.fieldRemarks.reportingPeriod:id,name',
            ])
            ->whereBelongsTo($reportingPeriod)
            ->whereIn('status', [AccomplishmentStatus::UnderReview, AccomplishmentStatus::Accepted])
            ->whereHas('submissions')
            ->whereHas(
                'planItem.keyResultArea.operationalPlan',
                fn (Builder $query): Builder => $query
                    ->whereBelongsTo($academicYear)
                    ->whereIn('organizational_unit_id', $this->scope->visibleUnitIds($user))
                    ->where('status', OperationalPlanStatus::Approved),
            )
            ->orderByRaw('COALESCE(resubmitted_at, submitted_at)')
            ->orderBy('id')
            ->get()
            ->filter(fn (Accomplishment $accomplishment): bool => $user->isSystemAdmin()
                ? $accomplishment->status === AccomplishmentStatus::UnderReview
                : Gate::forUser($user)->allows('create', [Evaluation::class, $accomplishment]))
            ->map(fn (Accomplishment $accomplishment): array => EvaluationData::queueItem($accomplishment, $user))
            ->all();

        return array_values($queue);
    }

    private function renderEmptyQueue(): Response
    {
        return Inertia::render('evaluations/index', [
            'academicYear' => null,
            'reportingPeriods' => [],
            'selectedReportingPeriod' => null,
            'queue' => [],
            'ratingOptions' => EvaluationRating::options(),
        ]);
    }
}
