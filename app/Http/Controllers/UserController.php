<?php

namespace App\Http\Controllers;

use App\Actions\Users\CreateUser;
use App\Actions\Users\UpdateUser;
use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Department;
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

        $users = User::query()
            ->with('department:id,name,code')
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'roleLabel' => $user->role->label(),
                'department' => $user->department ? [
                    'id' => $user->department->id,
                    'name' => $user->department->name,
                    'code' => $user->department->code,
                ] : null,
                'emailVerifiedAt' => $user->email_verified_at?->toISOString(),
            ]);

        $departments = Department::query()
            ->select(['id', 'name', 'code'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('administration/users/index', [
            'users' => $users,
            'departments' => $departments,
            'roles' => UserRole::options(),
        ]);
    }

    public function store(
        StoreUserRequest $request,
        string $currentTeam,
        CreateUser $createUser,
    ): RedirectResponse {
        $createUser->handle($request->validatedData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return back();
    }

    public function update(
        UpdateUserRequest $request,
        string $currentTeam,
        User $user,
        UpdateUser $updateUser,
    ): RedirectResponse {
        $updateUser->handle($user, $request->validatedData());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return back();
    }
}
