<?php

namespace App\Actions\KeyResultAreas;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReorderKeyResultAreas
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  list<int>  $orderedIds
     */
    public function handle(OperationalPlan $operationalPlan, User $actor, array $orderedIds): void
    {
        DB::transaction(function () use ($operationalPlan, $actor, $orderedIds): void {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('reorder', [KeyResultArea::class, $lockedOperationalPlan]);

            $keyResultAreas = KeyResultArea::query()
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if (collect($orderedIds)->sort()->values()->all() !== $keyResultAreas->keys()->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'ordered_ids' => __('The KRA order changed. Refresh the page and try again.'),
                ]);
            }

            foreach ($orderedIds as $index => $keyResultAreaId) {
                $keyResultAreas->get($keyResultAreaId)?->update([
                    'sort_order' => $index + 1,
                    'updated_by' => $actor->id,
                ]);
            }
        });
    }
}
