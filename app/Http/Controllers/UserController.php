<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(string $currentTeam): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('administration/users/index', [
            'users' => User::query()->with('organizationalUnitAssignments.organizationalUnit')->orderBy('name')->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id, 'name' => $user->name, 'email' => $user->email,
                    'role' => $user->role->value, 'roleLabel' => $user->roleLabel(),
                    'assignments' => $user->organizationalUnitAssignments->map(fn ($assignment): array => [
                        'organizational_unit_id' => $assignment->organizational_unit_id,
                        'unit_name' => $assignment->organizationalUnit->name,
                        'assignment_type' => $assignment->assignment_type->value,
                        'position_title' => $assignment->position_title,
                        'is_primary' => $assignment->is_primary, 'is_active' => $assignment->is_active,
                        'starts_on' => $assignment->starts_on?->toDateString(),
                        'ends_on' => $assignment->ends_on?->toDateString(),
                    ]),
                    'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
                ]),
            'organizationalUnits' => OrganizationalUnit::query()->orderBy('name')->get(['id', 'name', 'code', 'is_active']),
            'roles' => UserRole::options(),
        ]);
    }

    public function store(StoreUserRequest $request, string $currentTeam, CreateUser $createUser): RedirectResponse
    {
        $createUser->handle($request->validatedData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return back();
    }

    public function update(UpdateUserRequest $request, string $currentTeam, User $user, UpdateUser $updateUser): RedirectResponse
    {
        $updateUser->handle($user, $request->validatedData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }
}
