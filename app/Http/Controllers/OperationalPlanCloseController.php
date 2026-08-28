<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\CloseOperationalPlan;
use App\Http\Requests\CloseOperationalPlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OperationalPlanCloseController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        CloseOperationalPlanRequest $request,
        string $currentTeam,
        CloseOperationalPlan $closeOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $closeOperationalPlan->handle($request->operationalPlan(), $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan closed.')]);

        return back();
    }
}
