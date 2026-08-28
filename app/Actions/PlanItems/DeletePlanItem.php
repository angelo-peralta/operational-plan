<?php

namespace App\Actions\PlanItems;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DeletePlanItem
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    public function handle(PlanItem $planItem, User $actor): void
    {
        DB::transaction(function () use ($planItem, $actor): void {
            $keyResultArea = KeyResultArea::query()->findOrFail($planItem->key_result_area_id);
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);
            $lockedPlanItem = PlanItem::query()
                ->whereKey($planItem->id)
                ->whereBelongsTo($lockedKeyResultArea)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPlanItem->setRelation('keyResultArea', $lockedKeyResultArea);

            Gate::forUser($actor)->authorize('delete', $lockedPlanItem);

            $lockedPlanItem->delete();
        });
    }
}
