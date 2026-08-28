<?php

namespace App\Http\Controllers;

use App\Actions\PlanItems\CreatePlanItem;
use App\Actions\PlanItems\DeletePlanItem;
use App\Actions\PlanItems\ReorderPlanItems;
use App\Actions\PlanItems\UpdatePlanItem;
use App\Http\Requests\DeletePlanItemRequest;
use App\Http\Requests\ReorderPlanItemsRequest;
use App\Http\Requests\StorePlanItemRequest;
use App\Http\Requests\UpdatePlanItemRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlanItemController extends Controller
{
    public function store(
        StorePlanItemRequest $request,
        string $currentTeam,
        CreatePlanItem $createPlanItem,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $createPlanItem->handle(
            $request->keyResultArea(),
            $actor,
            $request->validatedData(),
            $request->coAccountableDepartmentIds(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan Item added.')]);

        return back();
    }

    public function update(
        UpdatePlanItemRequest $request,
        string $currentTeam,
        UpdatePlanItem $updatePlanItem,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $updatePlanItem->handle(
            $request->planItem(),
            $actor,
            $request->validatedData(),
            $request->coAccountableDepartmentIds(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan Item updated.')]);

        return back();
    }

    public function destroy(
        DeletePlanItemRequest $request,
        string $currentTeam,
        DeletePlanItem $deletePlanItem,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $deletePlanItem->handle($request->planItem(), $actor);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan Item removed.')]);

        return back();
    }

    public function reorder(
        ReorderPlanItemsRequest $request,
        string $currentTeam,
        ReorderPlanItems $reorderPlanItems,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);

        $reorderPlanItems->handle(
            $request->keyResultArea(),
            $actor,
            $request->orderedIds(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Plan Item order updated.')]);

        return back();
    }
}
