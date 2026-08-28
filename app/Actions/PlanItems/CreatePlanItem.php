<?php

namespace App\Actions\PlanItems;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\PlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreatePlanItem
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  array<string, mixed>  $data
     * @param  list<int>  $coAccountableDepartmentIds
     */
    public function handle(
        KeyResultArea $keyResultArea,
        User $actor,
        array $data,
        array $coAccountableDepartmentIds,
    ): PlanItem {
        return DB::transaction(function () use ($keyResultArea, $actor, $data, $coAccountableDepartmentIds): PlanItem {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);

            Gate::forUser($actor)->authorize('create', [PlanItem::class, $lockedKeyResultArea]);

            $sortOrder = ((int) $lockedKeyResultArea->planItems()->max('sort_order')) + 1;
            $planItem = $lockedKeyResultArea->planItems()->create([
                ...$data,
                'sort_order' => $sortOrder,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $planItem->coAccountableDepartments()->sync($coAccountableDepartmentIds);

            return $planItem->load('coAccountableDepartments');
        });
    }
}
