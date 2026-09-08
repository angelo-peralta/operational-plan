<?php

namespace App\Http\Controllers;

use App\Actions\Accomplishments\SubmitAccomplishment;
use App\Enums\AccomplishmentStatus;
use App\Http\Requests\SubmitAccomplishmentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class AccomplishmentSubmissionController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        SubmitAccomplishmentRequest $request,
        string $currentTeam,
        SubmitAccomplishment $submitAccomplishment,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $isResubmission = $request->accomplishment()->status === AccomplishmentStatus::Returned;

        $submitAccomplishment->handle($request->accomplishment(), $actor);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $isResubmission
                ? __('Accomplishment resubmitted for review.')
                : __('Accomplishment submitted for review.'),
        ]);

        return back();
    }
}
