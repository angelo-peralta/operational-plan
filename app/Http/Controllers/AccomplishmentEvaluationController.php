<?php

namespace App\Http\Controllers;

use App\Actions\Accomplishments\EvaluateAccomplishment;
use App\Enums\EvaluationDecision;
use App\Http\Requests\StoreEvaluationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AccomplishmentEvaluationController extends Controller
{
    /**
     * Store an immutable evaluation for the current submission revision.
     */
    public function __invoke(
        StoreEvaluationRequest $request,
        string $currentTeam,
        EvaluateAccomplishment $evaluateAccomplishment,
    ): RedirectResponse {
        $reviewer = $request->user();
        assert($reviewer instanceof User);

        $decision = $request->decision();
        $evaluateAccomplishment->handle(
            $request->accomplishment(),
            $reviewer,
            $decision,
            $request->rating(),
            $request->remarks(),
            $request->isOverride(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => match ($decision) {
                EvaluationDecision::Accepted => __('Accomplishment accepted and rated.'),
                EvaluationDecision::RequestRevision => __('Accomplishment revision requested.'),
                EvaluationDecision::RequestAdditionalEvidence => __('Additional evidence requested.'),
                EvaluationDecision::Rejected => __('Accomplishment rejected.'),
            },
        ]);

        return back();
    }
}
