<?php

namespace App\Http\Controllers;

use App\Actions\Evidence\UploadEvidence;
use App\Http\Requests\StoreEvidenceRequest;
use App\Http\Requests\ViewEvidenceRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function store(
        StoreEvidenceRequest $request,
        string $currentTeam,
        UploadEvidence $uploadEvidence,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $uploadEvidence->handle(
            $request->accomplishment(),
            $actor,
            $request->evidenceFile(),
            $request->validatedMetadata(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Evidence uploaded securely.')]);

        return back();
    }

    public function show(ViewEvidenceRequest $request, string $currentTeam): StreamedResponse
    {
        $evidence = $request->evidence();
        abort_unless(Storage::disk('local')->exists($evidence->stored_path), 404);

        return Storage::disk('local')->response(
            $evidence->stored_path,
            $evidence->original_filename,
        );
    }

    public function download(ViewEvidenceRequest $request, string $currentTeam): StreamedResponse
    {
        $evidence = $request->evidence();
        abort_unless(Storage::disk('local')->exists($evidence->stored_path), 404);

        return Storage::disk('local')->download($evidence->stored_path, $evidence->original_filename);
    }
}
