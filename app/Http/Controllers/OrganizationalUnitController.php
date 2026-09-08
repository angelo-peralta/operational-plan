<?php

namespace App\Http\Controllers;

use App\Actions\OrganizationalUnits\SaveOrganizationalUnit;
use App\Enums\OrganizationalUnitType;
use App\Http\Requests\StoreOrganizationalUnitRequest;
use App\Http\Requests\UpdateOrganizationalUnitRequest;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationalUnitController extends Controller
{
    public function index(string $currentTeam): Response
    {
        Gate::authorize('viewAny', OrganizationalUnit::class);

        return Inertia::render('administration/organizational-units/index', [
            'organizationalUnits' => OrganizationalUnit::query()->with('parent:id,name')->withCount('assignments')
                ->orderBy('sort_order')->orderBy('name')->get()->map(fn (OrganizationalUnit $unit): array => [
                    'id' => $unit->id, 'parentId' => $unit->parent_id, 'name' => $unit->name,
                    'code' => $unit->code, 'acronym' => $unit->acronym, 'type' => $unit->type->value,
                    'typeLabel' => $unit->type->label(), 'description' => $unit->description,
                    'isActive' => $unit->is_active, 'sortOrder' => $unit->sort_order,
                    'assignmentCount' => $unit->getAttribute('assignments_count'),
                    'parent' => $unit->parent ? ['id' => $unit->parent->id, 'name' => $unit->parent->name] : null,
                ]),
            'unitTypes' => OrganizationalUnitType::options(),
        ]);
    }

    public function store(StoreOrganizationalUnitRequest $request, string $currentTeam, SaveOrganizationalUnit $save): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $save->handle($actor, $request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organizational unit created.')]);

        return back();
    }

    public function update(UpdateOrganizationalUnitRequest $request, string $currentTeam, OrganizationalUnit $organizationalUnit, SaveOrganizationalUnit $save): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $save->handle($actor, $request->validated(), $organizationalUnit);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Organizational unit updated.')]);

        return back();
    }
}
