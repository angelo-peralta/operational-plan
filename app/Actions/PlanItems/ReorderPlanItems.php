<?php

namespace App\Actions\PlanItems;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReorderPlanItems
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  list<int>  $orderedIds
     */
    public function handle(KeyResultArea $keyResultArea, User $actor, array $orderedIds): void
    {
        DB::transaction(function () use ($keyResultArea, $actor, $orderedIds): void {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);

            Gate::forUser($actor)->authorize('reorder', [PlanItem::class, $lockedKeyResultArea]);

            $planItems = PlanItem::query()
                ->whereBelongsTo($lockedKeyResultArea)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (collect($orderedIds)->sort()->values()->all() !== $planItems->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'ordered_ids' => __('The Plan Item order changed. Refresh the page and try again.'),
                ]);
            }

            foreach ($orderedIds as $index => $planItemId) {
                $planItems->get($planItemId)?->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $actor->id,
                ]);
            }
        });
    }
}
