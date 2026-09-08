<?php

namespace App\Actions\PlanItems;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdatePlanItem
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $coAccountableOrganizationalUnitIds
     */
    public function handle(
        PlanItem $planItem,
        User $actor,
        array $data,
        array $coAccountableOrganizationalUnitIds,
    ): PlanItem {
        return DB::transaction(function () use ($planItem, $actor, $data, $coAccountableOrganizationalUnitIds): PlanItem {
            $keyResultArea = KeyResultArea::query()->findOrFail($planItem->key_result_area_id);
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);
            $lockedPlanItem = PlanItem::query()
                ->whereKey($planItem->id)
                ->whereBelongsTo($lockedKeyResultArea)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedPlanItem->setRelation('keyResultArea', $lockedKeyResultArea);

            Gate::forUser($actor)->authorize('update', $lockedPlanItem);

            $lockedPlanItem->update([
                ...$data,
                'updated_by' => $actor->id,
            ]);
            $lockedPlanItem->coAccountableOrganizationalUnits()->sync($coAccountableOrganizationalUnitIds);

            return $lockedPlanItem->refresh()->load('coAccountableOrganizationalUnits');
        });
    }
}
