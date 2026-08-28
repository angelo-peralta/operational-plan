<?php

namespace App\Actions\KeyResultAreas;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateKeyResultArea
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  array{code: string|null, name: string, description: string|null}  $data
     */
    public function handle(KeyResultArea $keyResultArea, User $actor, array $data): KeyResultArea
    {
        return DB::transaction(function () use ($keyResultArea, $actor, $data): KeyResultArea {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);

            Gate::forUser($actor)->authorize('update', $lockedKeyResultArea);

            $lockedKeyResultArea->update([
                ...$data,
                'updated_by' => $actor->id,
            ]);

            return $lockedKeyResultArea->refresh();
        });
    }
}
