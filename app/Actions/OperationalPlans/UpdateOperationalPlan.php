<?php

namespace App\Actions\OperationalPlans;

use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateOperationalPlan
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  array{accountable_user_id: int|null, accountable_name: string|null, accountable_position: string|null, goal: string}  $data
     */
    public function handle(OperationalPlan $operationalPlan, User $actor, array $data): OperationalPlan
    {
        return DB::transaction(function () use ($operationalPlan, $actor, $data): OperationalPlan {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('update', $lockedOperationalPlan);

            $lockedOperationalPlan->update([
                ...$data,
                'updated_by' => $actor->id,
            ]);

            return $lockedOperationalPlan->refresh();
        });
    }
}
