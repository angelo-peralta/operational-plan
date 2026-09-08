<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(string $currentTeam): Response
    {
        Gate::authorize('viewAny', Role::class);

        return Inertia::render('administration/roles/index', ['roles' => UserRole::options()]);
    }

    public function store(StoreRoleRequest $request, string $currentTeam): RedirectResponse
    {
        abort(403, 'The MVP uses four fixed system roles.');
    }
}
