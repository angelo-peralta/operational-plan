<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use App\Models\OrganizationalUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class DepartmentController extends Controller
{
    public function index(string $currentTeam): RedirectResponse
    {
        Gate::authorize('viewAny', OrganizationalUnit::class);

        return to_route('administration.organizational-units.index', ['current_team' => $currentTeam]);
    }

    public function store(StoreDepartmentRequest $request, string $currentTeam): RedirectResponse
    {
        abort(403, 'Use organizational unit administration.');
    }

    public function update(UpdateDepartmentRequest $request, string $currentTeam, Department $department): RedirectResponse
    {
        abort(403, 'Use organizational unit administration.');
    }
}
