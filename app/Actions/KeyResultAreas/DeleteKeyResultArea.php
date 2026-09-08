<?php

namespace App\Actions\KeyResultAreas;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class DeleteKeyResultArea
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    public function handle(KeyResultArea $keyResultArea, User $actor): void
    {
        DB::transaction(function () use ($keyResultArea, $actor): void {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($keyResultArea->operational_plan_id);
            $lockedKeyResultArea = KeyResultArea::query()
                ->whereKey($keyResultArea->id)
                ->whereBelongsTo($lockedOperationalPlan)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedKeyResultArea->setRelation('operationalPlan', $lockedOperationalPlan);

            Gate::forUser($actor)->authorize('delete', $lockedKeyResultArea);

            if ($lockedKeyResultArea->planItems()->exists()) {
                throw ValidationException::withMessages([
                    'key_result_area' => __('Key Result Areas with Plan Items cannot be removed.'),
                ]);
            }

            if ($lockedKeyResultArea->remarks()->exists()) {
                throw ValidationException::withMessages([
                    'key_result_area' => __('Key Result Areas with review remarks cannot be removed.'),
                ]);
            }

            $lockedKeyResultArea->delete();
        });
    }
}
