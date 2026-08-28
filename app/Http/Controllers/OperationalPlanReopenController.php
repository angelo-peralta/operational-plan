<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\ReopenOperationalPlan;
use App\Http\Requests\ReopenOperationalPlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OperationalPlanReopenController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ReopenOperationalPlanRequest $request,
        string $currentTeam,
        ReopenOperationalPlan $reopenOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $reopenOperationalPlan->handle(
            $request->operationalPlan(),
            $actor,
            $request->remarks(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan reopened for revision.')]);

        return back();
    }
}
