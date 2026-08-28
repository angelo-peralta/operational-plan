<?php

namespace App\Actions\OperationalPlans;

use App\Enums\OperationalPlanStatus;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitOperationalPlan
{
    public function __construct(
        private RecordOperationalPlanStatus $recordStatus,
        private LockOperationalPlanForMutation $lockOperationalPlan,
    ) {}

    public function handle(OperationalPlan $operationalPlan, User $actor): OperationalPlan
    {
        return DB::transaction(function () use ($operationalPlan, $actor): OperationalPlan {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('submit', $lockedOperationalPlan);

            $keyResultAreas = $lockedOperationalPlan->keyResultAreas()
                ->withCount('planItems')
                ->get();

            if ($keyResultAreas->isEmpty()) {
                throw ValidationException::withMessages([
                    'plan' => __('Add at least one Key Result Area before submitting the plan.'),
                ]);
            }

            if ($keyResultAreas->contains(fn (KeyResultArea $keyResultArea): bool => $keyResultArea->plan_items_count === 0)) {
                throw ValidationException::withMessages([
                    'plan' => __('Every Key Result Area must contain at least one Plan Item before submission.'),
                ]);
            }

            if (blank($lockedOperationalPlan->goal) || (
                $lockedOperationalPlan->accountable_user_id === null
                && blank($lockedOperationalPlan->accountable_name)
                && blank($lockedOperationalPlan->accountable_position)
            )) {
                throw ValidationException::withMessages([
                    'plan' => __('Complete the accountable details and goal before submitting the plan.'),
                ]);
            }

            $fromStatus = $lockedOperationalPlan->status;
            $lockedOperationalPlan->update([
                'status' => OperationalPlanStatus::Submitted,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->recordStatus->handle(
                $lockedOperationalPlan,
                $actor,
                $fromStatus,
                OperationalPlanStatus::Submitted,
            );

            return $lockedOperationalPlan->refresh();
        });
    }
}
