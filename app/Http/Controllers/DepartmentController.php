<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(string $currentTeam): Response
    {
        Gate::authorize('create', Department::class);

        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'code' => $department->code,
                'description' => $department->description,
                'isActive' => $department->is_active,
                'userCount' => $department->users_count,
            ]);

        return Inertia::render('administration/departments/index', [
            'departments' => $departments,
        ]);
    }

    public function store(StoreDepartmentRequest $request, string $currentTeam): RedirectResponse
    {
        Department::query()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department created.')]);

        return back();
    }

    public function update(
        UpdateDepartmentRequest $request,
        string $currentTeam,
        Department $department,
    ): RedirectResponse {
        $department->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Department updated.')]);

        return back();
    }
}
