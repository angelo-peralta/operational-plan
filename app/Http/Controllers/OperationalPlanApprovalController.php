<?php

namespace App\Http\Controllers;

use App\Actions\OperationalPlans\ApproveOperationalPlan;
use App\Http\Requests\ApproveOperationalPlanRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class OperationalPlanApprovalController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ApproveOperationalPlanRequest $request,
        string $currentTeam,
        ApproveOperationalPlan $approveOperationalPlan,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $approveOperationalPlan->handle($request->operationalPlan(), $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Operational Plan approved.')]);

        return back();
    }
}
