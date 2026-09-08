<?php

namespace App\Http\Middleware;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\OrganizationalUnitType;
use App\Models\AcademicYear;
use App\Models\Evaluation;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private ResolveOrganizationalScope $scope) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        if ($user !== null) {
            $user->loadMissing([
                'organizationalUnitAssignments.organizationalUnit',
            ]);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user ? $this->userData($user) : null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'currentTeam' => fn () => $user?->currentTeam ? $user->toUserTeam($user->currentTeam) : null,
            'teams' => fn () => $user?->toUserTeams(includeCurrent: true) ?? [],
            'academicYears' => fn () => $user === null
                ? []
                : AcademicYear::query()
                    ->orderByDesc('is_current')
                    ->orderByDesc('start_year')
                    ->get()
                    ->map(fn (AcademicYear $academicYear): array => $this->academicYearData($academicYear)),
            'selectedAcademicYear' => fn () => $this->selectedAcademicYearData($request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function userData(User $user): array
    {
        $assignedIds = $this->scope->assignedUnitIds($user);
        $assignments = $user->organizationalUnitAssignments
            ->filter(fn ($assignment): bool => in_array($assignment->organizational_unit_id, $assignedIds, true));
        $universityScope = $user->isExecutive() && $assignments->contains(
            fn ($assignment): bool => $assignment->organizationalUnit->parent_id === null
                && $assignment->organizationalUnit->type === OrganizationalUnitType::University,
        );

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'role' => $user->accessLevel()->value,
            'scopeLabel' => $user->isSystemAdmin() ? 'System administration' : ($universityScope ? 'University-wide' : ($assignments->map(fn ($assignment): string => $assignment->organizationalUnit->name)->implode(', ') ?: 'No organizational assignment')),
            'organizationalAssignments' => $assignments->map(fn ($assignment): array => [
                'id' => $assignment->organizational_unit_id,
                'name' => $assignment->organizationalUnit->name,
                'positionTitle' => $assignment->position_title,
                'assignmentType' => $assignment->assignment_type->value,
            ])->values(),
            'permissions' => [
                'viewPlans' => $user->can('viewAny', OperationalPlan::class),
                'viewEvaluations' => $user->can('viewAny', Evaluation::class),
            ],
            'roleLabel' => $user->roleLabel(),
        ];
    }

    /**
     * @return array{id: int, name: string, status: string, isCurrent: bool, startYear: int, endYear: int}
     */
    private function academicYearData(AcademicYear $academicYear): array
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

    /**
     * @return array{id: int, name: string, status: string, isCurrent: bool, startYear: int, endYear: int}|null
     */
    private function selectedAcademicYearData(Request $request): ?array
    {
        $academicYear = $request->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);

        return $academicYear instanceof AcademicYear
            ? $this->academicYearData($academicYear)
            : null;
    }
}
