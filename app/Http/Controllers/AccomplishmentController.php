<?php

namespace App\Http\Controllers;

use App\Actions\Accomplishments\CreateAccomplishment;
use App\Actions\Accomplishments\UpdateAccomplishment;
use App\Http\Requests\StoreAccomplishmentRequest;
use App\Http\Requests\UpdateAccomplishmentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AccomplishmentController extends Controller
{
    public function store(
        StoreAccomplishmentRequest $request,
        string $currentTeam,
        CreateAccomplishment $createAccomplishment,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $createAccomplishment->handle(
            $request->planItem(),
            $request->reportingPeriod(),
            $actor,
            $request->validatedData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Accomplishment draft saved.')]);

        return back();
    }

    public function update(
        UpdateAccomplishmentRequest $request,
        string $currentTeam,
        UpdateAccomplishment $updateAccomplishment,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $updateAccomplishment->handle(
            $request->accomplishment(),
            $actor,
            $request->validatedData(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Accomplishment draft updated.')]);

        return back();
    }
}
