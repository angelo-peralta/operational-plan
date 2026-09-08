<?php

namespace App\Http\Controllers;

use App\Actions\KeyResultAreaTemplates\DeleteKeyResultAreaTemplate;
use App\Actions\KeyResultAreaTemplates\SaveKeyResultAreaTemplate;
use App\Http\Requests\StoreKeyResultAreaTemplateRequest;
use App\Http\Requests\UpdateKeyResultAreaTemplateRequest;
use App\Models\KeyResultAreaTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KeyResultAreaTemplateController extends Controller
{
    public function store(
        StoreKeyResultAreaTemplateRequest $request,
        string $currentTeam,
        SaveKeyResultAreaTemplate $save,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $save->handle($actor, $request->validatedData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Global KRA template created.')]);

        return back();
    }

    public function update(
        UpdateKeyResultAreaTemplateRequest $request,
        string $currentTeam,
        KeyResultAreaTemplate $keyResultAreaTemplate,
        SaveKeyResultAreaTemplate $save,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $save->handle($actor, $request->validatedData(), $keyResultAreaTemplate);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Global KRA template updated.')]);

        return back();
    }

    public function destroy(
        Request $request,
        string $currentTeam,
        KeyResultAreaTemplate $keyResultAreaTemplate,
        DeleteKeyResultAreaTemplate $delete,
    ): RedirectResponse {
        $actor = $request->user();
        abort_unless($actor instanceof User, 403);

        $delete->handle($actor, $keyResultAreaTemplate);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Global KRA template removed.')]);

        return back();
    }
}
