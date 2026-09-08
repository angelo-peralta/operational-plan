<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\CreateOperationalPlan;
use App\Actions\OperationalPlans\UpdateOperationalPlan;
use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Data\OperationalPlanData;
use App\Data\OperationalPlanReviewData;
use App\Enums\RemarkCategory;
use App\Enums\RemarkField;
use App\Enums\TargetOperator;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Http\Requests\StoreOperationalPlanRequest;
use App\Http\Requests\UpdateOperationalPlanRequest;
use App\Http\Requests\ViewOperationalPlanRequest;
use App\Models\AcademicYear;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationalPlanController extends Controller
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    public function index(string $currentTeam, Request $request): Response
    {
        Gate::authorize('viewAny', OperationalPlan::class);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);
        $user = $request->user();

        return Inertia::render('operational-plans/index', [
            'operationalPlans' => $academicYear instanceof AcademicYear && $user instanceof User
                ? $this->operationalPlans($academicYear, $user)
                : [],
            'targetOrganizationalUnits' => $academicYear instanceof AcademicYear && $user instanceof User
                ? $this->targetOrganizationalUnits($academicYear, $user)
                : [],
            'accountableUsers' => $academicYear instanceof AcademicYear && $user instanceof User
                ? $this->accountableUsers($academicYear, $user)
                : [],
        ]);
    }

    public function store(
        StoreOperationalPlanRequest $request,
        string $currentTeam,
        CreateOperationalPlan $createOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $operationalPlan = $createOperationalPlan->handle(
            $request->selectedAcademicYear(),
            $request->organizationalUnit(),
            $actor,
            $request->validatedPlanData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan created.')]);

        return to_route('operational-plans.show', [
            'current_team' => $currentTeam,
            'operational_plan' => $operationalPlan,
        ]);
    }

    public function show(ViewOperationalPlanRequest $request, string $currentTeam): Response
    {
        $operationalPlan = $request->operationalPlan();
        $actor = $request->user();
        assert($actor instanceof User);
        $reportingPeriods = ReportingPeriod::query()
            ->whereBelongsTo($operationalPlan->academicYear)
            ->orderBy('sequence')
            ->get();
        $selectedReportingPeriod = $this->selectedReportingPeriod($request, $reportingPeriods);
        $operationalPlan = $this->loadDetail($operationalPlan, $selectedReportingPeriod);

        return Inertia::render('operational-plans/show', [
            'operationalPlan' => OperationalPlanData::detail($operationalPlan, $selectedReportingPeriod, $actor),
            'permissions' => $this->permissions($actor, $operationalPlan),
            'accountableUsers' => $this->accountableUsersForUnits([$operationalPlan->organizational_unit_id]),
            'coAccountableOrganizationalUnits' => OrganizationalUnit::query()
                ->select(['id', 'name', 'code', 'is_active'])
                ->orderBy('name')
                ->get()
                ->map(fn (OrganizationalUnit $organizationalUnit): array => [
                    'id' => $organizationalUnit->id,
                    'name' => $organizationalUnit->name,
                    'code' => $organizationalUnit->code,
                    'isActive' => $organizationalUnit->is_active,
                ]),
            'targetOperators' => TargetOperator::options(),
            'reportingPeriods' => $reportingPeriods
                ->map(fn (ReportingPeriod $reportingPeriod): array => [
                    ...$reportingPeriod->only(['id', 'name', 'code', 'sequence', 'starts_on', 'ends_on']),
                    'startsOn' => $reportingPeriod->starts_on?->toDateString(),
                    'endsOn' => $reportingPeriod->ends_on?->toDateString(),
                    'status' => $reportingPeriod->status->value,
                    'acceptsSubmissions' => $reportingPeriod->acceptsSubmissions(),
                ])
                ->values(),
            'selectedReportingPeriod' => $selectedReportingPeriod
                ? [
                    'id' => $selectedReportingPeriod->id,
                    'name' => $selectedReportingPeriod->name,
                    'code' => $selectedReportingPeriod->code,
                    'sequence' => $selectedReportingPeriod->sequence,
                    'startsOn' => $selectedReportingPeriod->starts_on?->toDateString(),
                    'endsOn' => $selectedReportingPeriod->ends_on?->toDateString(),
                    'status' => $selectedReportingPeriod->status->value,
                    'acceptsSubmissions' => $selectedReportingPeriod->acceptsSubmissions(),
                ]
                : null,
            'review' => OperationalPlanReviewData::forPlan($operationalPlan, $actor),
            'remarkOptions' => [
                'categories' => array_map(fn (RemarkCategory $category): string => $category->value, RemarkCategory::cases()),
                'fields' => array_map(
                    fn (RemarkField $field): string => $field->value,
                    array_filter(
                        RemarkField::cases(),
                        fn (RemarkField $field): bool => ! in_array($field, [RemarkField::AccomplishmentText, RemarkField::Evidence, RemarkField::Evaluation], true),
                    ),
                ),
            ],
        ]);
    }

    public function update(
        UpdateOperationalPlanRequest $request,
        string $currentTeam,
        UpdateOperationalPlan $updateOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $updateOperationalPlan->handle(
            $request->operationalPlan(),
            $actor,
            $request->validatedPlanData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan updated.')]);

        return back();
    }

    /** @return list<array<string, mixed>> */
    private function operationalPlans(AcademicYear $academicYear, User $user): array
    {
        $operationalPlans = OperationalPlan::query()
            ->with([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'organizationalUnit:id,name,code',
                'accountableUser:id,name',
                'statusHistories:id,operational_plan_id,actor_id,from_status,to_status,remarks,created_at',
            ])
            ->withCount(['keyResultAreas', 'planItems'])
            ->whereBelongsTo($academicYear)
            ->whereIn('organizational_unit_id', $this->scope->visibleUnitIds($user))
            ->latest('updated_at')
            ->get()
            ->map(fn (OperationalPlan $operationalPlan): array => OperationalPlanData::summary($operationalPlan))
            ->all();

        return array_values($operationalPlans);
    }

    /** @return list<array{id: int, name: string, code: string|null}> */
    private function targetOrganizationalUnits(AcademicYear $academicYear, User $user): array
    {
        if (! $academicYear->isOpen()) {
            return [];
        }

        $plannedOrganizationalUnitIds = OperationalPlan::query()
            ->whereBelongsTo($academicYear)
            ->pluck('organizational_unit_id');

        $organizationalUnits = OrganizationalUnit::query()
            ->select(['id', 'name', 'code'])
            ->where('is_active', true)
            ->whereNotIn('id', $plannedOrganizationalUnitIds)
            ->whereKey($this->scope->manageableUnitIds($user))
            ->orderBy('name')
            ->get()
            ->map(fn (OrganizationalUnit $organizationalUnit): array => [
                'id' => $organizationalUnit->id,
                'name' => $organizationalUnit->name,
                'code' => $organizationalUnit->code,
            ])
            ->all();

        return array_values($organizationalUnits);
    }

    /** @return list<array{id: int, name: string, organizationalUnitId: int}> */
    private function accountableUsers(AcademicYear $academicYear, User $user): array
    {
        $organizationalUnitIds = array_column($this->targetOrganizationalUnits($academicYear, $user), 'id');

        return $this->accountableUsersForUnits($organizationalUnitIds);
    }

    /** @param list<int> $unitIds
     * @return list<array{id: int, name: string, organizationalUnitId: int}>
     */
    private function accountableUsersForUnits(array $unitIds): array
    {
        return array_values(User::query()
            ->whereHas('organizationalUnitAssignments', fn (Builder $query): Builder => $query->whereIn('organizational_unit_id', $unitIds))
            ->orderBy('name')->get()
            ->flatMap(fn (User $user): array => array_map(
                fn (int $unitId): array => ['id' => $user->id, 'name' => $user->name, 'organizationalUnitId' => $unitId],
                array_values(array_intersect($unitIds, $this->scope->assignedUnitIds($user))),
            ))->values()->all());
    }

    /** @param Collection<int, ReportingPeriod> $reportingPeriods */
    private function selectedReportingPeriod(Request $request, Collection $reportingPeriods): ?ReportingPeriod
    {
        if ($request->query->has('reporting_period')) {
            $reportingPeriodId = $request->query('reporting_period');
            abort_unless(is_string($reportingPeriodId) && ctype_digit($reportingPeriodId), 404);

            return $reportingPeriods->firstOrFail(fn (ReportingPeriod $reportingPeriod): bool => $reportingPeriod->id === (int) $reportingPeriodId);
        }

        return $reportingPeriods->first(fn (ReportingPeriod $reportingPeriod): bool => $reportingPeriod->acceptsSubmissions())
            ?? $reportingPeriods->first();
    }

    private function loadDetail(OperationalPlan $operationalPlan, ?ReportingPeriod $reportingPeriod): OperationalPlan
    {
        return $operationalPlan
            ->load([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'organizationalUnit:id,name,code',
                'accountableUser:id,name',
                'keyResultAreas.planItems.coAccountableOrganizationalUnits:id,name,code',
                'keyResultAreas.planItems.accomplishments' => function (Relation $relation) use ($reportingPeriod): void {
                    if ($reportingPeriod instanceof ReportingPeriod) {
                        $relation->whereBelongsTo($reportingPeriod);
                    }
                },
                'statusHistories.actor:id,name',
                'remarks.remarkable',
                'remarks.reportingPeriod:id,name',
                'remarks.author.organizationalUnitAssignments.organizationalUnit:id,type',
            ])
            ->loadCount(['keyResultAreas', 'planItems']);
    }

    /** @return array<string, bool> */
    private function permissions(User $user, OperationalPlan $operationalPlan): array
    {
        return [
            'updatePlan' => Gate::forUser($user)->allows('update', $operationalPlan),
            'submitPlan' => Gate::forUser($user)->allows('submit', $operationalPlan),
            'approvePlan' => Gate::forUser($user)->allows('approve', $operationalPlan),
            'returnPlan' => Gate::forUser($user)->allows('returnForRevision', $operationalPlan),
            'closePlan' => Gate::forUser($user)->allows('close', $operationalPlan),
            'reopenPlan' => Gate::forUser($user)->allows('reopen', $operationalPlan),
            'createKeyResultArea' => Gate::forUser($user)->allows('create', [KeyResultArea::class, $operationalPlan]),
            'updateKeyResultArea' => $operationalPlan->keyResultAreas->every(fn (KeyResultArea $keyResultArea): bool => Gate::forUser($user)->allows('update', $keyResultArea)),
            'deleteKeyResultArea' => Gate::forUser($user)->allows('delete', $operationalPlan->keyResultAreas->first() ?? new KeyResultArea(['operational_plan_id' => $operationalPlan->id])),
            'reorderKeyResultAreas' => Gate::forUser($user)->allows('reorder', [KeyResultArea::class, $operationalPlan]),
            'viewOfficial' => Gate::forUser($user)->allows('view', $operationalPlan),
            'createRemark' => Gate::forUser($user)->allows('create', [Remark::class, $operationalPlan]),
        ];
    }
}
