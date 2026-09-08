<?php

namespace App\Http\Controllers;

use App\Actions\Remarks\ChangeRemarkStatus;
use App\Actions\Remarks\CreateRemark;
use App\Enums\RemarkStatus;
use App\Http\Requests\StoreRemarkRequest;
use App\Http\Requests\UpdateRemarkStatusRequest;
use App\Models\OperationalPlan;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class RemarkController extends Controller
{
    public function store(StoreRemarkRequest $request, string $currentTeam, OperationalPlan $operationalPlan, CreateRemark $createRemark): RedirectResponse
    {
        $actor = $request->user();
        $target = $request->target();
        assert($actor instanceof User && $target !== null);
        $createRemark->handle($operationalPlan->id, $target, $actor, $request->remarkData());
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Remark added.')]);

        return back();
    }

    public function update(UpdateRemarkStatusRequest $request, string $currentTeam, OperationalPlan $operationalPlan, Remark $remark, ChangeRemarkStatus $changeStatus): RedirectResponse
    {
        abort_unless($remark->operational_plan_id === $operationalPlan->id, 404);
        $actor = $request->user();
        assert($actor instanceof User);
        $changeStatus->handle($remark, $actor, RemarkStatus::from($request->string('status')->toString()), $request->filled('note') ? $request->string('note')->toString() : null);
        Inertia::flash('toast', ['type' => 'success', 'message' => __('Remark status updated.')]);

        return back();
    }
}
