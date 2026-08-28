<?php

namespace App\Http\Controllers;

use App\Actions\KeyResultAreas\CreateKeyResultArea;
use App\Actions\KeyResultAreas\ReorderKeyResultAreas;
use App\Actions\KeyResultAreas\UpdateKeyResultArea;
use App\Http\Requests\ReorderKeyResultAreasRequest;
use App\Http\Requests\StoreKeyResultAreaRequest;
use App\Http\Requests\UpdateKeyResultAreaRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class KeyResultAreaController extends Controller
{
    public function store(
        StoreKeyResultAreaRequest $request,
        string $currentTeam,
        CreateKeyResultArea $createKeyResultArea,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $createKeyResultArea->handle(
            $request->operationalPlan(),
            $actor,
            $request->validatedData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Key Result Area added.')]);

        return back();
    }

    public function update(
        UpdateKeyResultAreaRequest $request,
        string $currentTeam,
        UpdateKeyResultArea $updateKeyResultArea,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $updateKeyResultArea->handle(
            $request->keyResultArea(),
            $actor,
            $request->validatedData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Key Result Area updated.')]);

        return back();
    }

    public function reorder(
        ReorderKeyResultAreasRequest $request,
        string $currentTeam,
        ReorderKeyResultAreas $reorderKeyResultAreas,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $reorderKeyResultAreas->handle(
            $request->operationalPlan(),
            $actor,
            $request->orderedIds(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('KRA order updated.')]);

        return back();
    }
}
