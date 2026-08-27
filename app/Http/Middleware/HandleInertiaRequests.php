<?php

namespace App\Http\Middleware;

use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
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
            $user->loadMissing('department:id,name,code');
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
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
            'role' => $user->role->value,
            'roleLabel' => $user->role->label(),
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
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
