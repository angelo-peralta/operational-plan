<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\ReturnOperationalPlan;
use App\Http\Requests\ReturnOperationalPlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OperationalPlanReturnController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ReturnOperationalPlanRequest $request,
        string $currentTeam,
        ReturnOperationalPlan $returnOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $returnOperationalPlan->handle(
            $request->operationalPlan(),
            $actor,
            $request->remarks(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan returned for revision.')]);

        return back();
    }
}
