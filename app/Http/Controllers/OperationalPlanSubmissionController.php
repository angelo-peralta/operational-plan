<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\SubmitOperationalPlan;
use App\Http\Requests\SubmitOperationalPlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OperationalPlanSubmissionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        SubmitOperationalPlanRequest $request,
        string $currentTeam,
        SubmitOperationalPlan $submitOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $submitOperationalPlan->handle($request->operationalPlan(), $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan submitted for review.')]);

        return back();
    }
}
