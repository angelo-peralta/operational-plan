<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\CreateOperationalPlan;
use App\Actions\OperationalPlans\UpdateOperationalPlan;
use App\Data\OperationalPlanData;
use App\Enums\TargetOperator;
use App\Http\Middleware\ResolveAcademicYearContext;
use App\Http\Requests\StoreOperationalPlanRequest;
use App\Http\Requests\UpdateOperationalPlanRequest;
use App\Http\Requests\ViewOperationalPlanRequest;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OperationalPlanController extends Controller
{
    public function index(string $currentTeam, Request $request): Response
    {
        Gate::authorize('viewAny', OperationalPlan::class);

        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);
        $user = $request->user();

        return Inertia::render('operational-plans/index', [
            'operationalPlans' => $academicYear instanceof AcademicYear && $user instanceof User
                ? $this->operationalPlans($academicYear, $user)
                : [],
            'targetDepartments' => $academicYear instanceof AcademicYear && $user instanceof User
                ? $this->targetDepartments($academicYear, $user)
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
            $request->department(),
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
        $operationalPlan = $this->loadDetail($request->operationalPlan());
        $actor = $request->user();
        assert($actor instanceof User);

        return Inertia::render('operational-plans/show', [
            'operationalPlan' => OperationalPlanData::detail($operationalPlan),
            'permissions' => $this->permissions($actor, $operationalPlan),
            'accountableUsers' => User::query()
                ->select(['id', 'name', 'department_id'])
                ->where('department_id', $operationalPlan->department_id)
                ->orderBy('name')
                ->get(),
            'coAccountableDepartments' => Department::query()
                ->select(['id', 'name', 'code', 'is_active'])
                ->orderBy('name')
                ->get()
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'code' => $department->code,
                    'isActive' => $department->is_active,
                ]),
            'targetOperators' => TargetOperator::options(),
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
                'department:id,name,code',
                'accountableUser:id,name',
                'statusHistories:id,operational_plan_id,actor_id,from_status,to_status,remarks,created_at',
            ])
            ->withCount(['keyResultAreas', 'planItems'])
            ->whereBelongsTo($academicYear)
            ->when(
                $user->isDepartmentUser(),
                fn (Builder $query): Builder => $query->where('department_id', $user->department_id),
            )
            ->latest('updated_at')
            ->get()
            ->map(fn (OperationalPlan $operationalPlan): array => OperationalPlanData::summary($operationalPlan))
            ->all();

        return array_values($operationalPlans);
    }

    /** @return list<array{id: int, name: string, code: string|null}> */
    private function targetDepartments(AcademicYear $academicYear, User $user): array
    {
        if (! $academicYear->isOpen()) {
            return [];
        }

        $plannedDepartmentIds = OperationalPlan::query()
            ->whereBelongsTo($academicYear)
            ->pluck('department_id');

        $departments = Department::query()
            ->select(['id', 'name', 'code'])
            ->where('is_active', true)
            ->whereNotIn('id', $plannedDepartmentIds)
            ->when(
                $user->isDepartmentUser(),
                fn (Builder $query): Builder => $query->whereKey($user->department_id),
            )
            ->when(
                ! $user->isSuperAdmin() && ! $user->isDepartmentUser(),
                fn (Builder $query): Builder => $query->whereKey(-1),
            )
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
            ])
            ->all();

        return array_values($departments);
    }

    /** @return list<array{id: int, name: string, departmentId: int}> */
    private function accountableUsers(AcademicYear $academicYear, User $user): array
    {
        $departmentIds = collect($this->targetDepartments($academicYear, $user))->pluck('id');

        $accountableUsers = User::query()
            ->select(['id', 'name', 'department_id'])
            ->whereNotNull('department_id')
            ->whereIn('department_id', $departmentIds)
            ->orderBy('name')
            ->get()
            ->map(fn (User $accountableUser): array => [
                'id' => $accountableUser->id,
                'name' => $accountableUser->name,
                'departmentId' => (int) $accountableUser->department_id,
            ])
            ->all();

        return array_values($accountableUsers);
    }

    private function loadDetail(OperationalPlan $operationalPlan): OperationalPlan
    {
        return $operationalPlan
            ->load([
                'academicYear:id,name,status,is_current,start_year,end_year',
                'department:id,name,code',
                'accountableUser:id,name',
                'keyResultAreas.planItems.coAccountableDepartments:id,name,code',
                'statusHistories.actor:id,name',
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
            'reorderKeyResultAreas' => Gate::forUser($user)->allows('reorder', [KeyResultArea::class, $operationalPlan]),
            'viewOfficial' => Gate::forUser($user)->allows('view', $operationalPlan),
        ];
    }
}
