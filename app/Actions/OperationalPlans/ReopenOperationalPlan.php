<?php

namespace App\Actions\OperationalPlans;

use App\Enums\OperationalPlanStatus;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ReopenOperationalPlan
{
    public function __construct(
        private RecordOperationalPlanStatus $recordStatus,
        private LockOperationalPlanForMutation $lockOperationalPlan,
    ) {}

    public function handle(
        OperationalPlan $operationalPlan,
        User $actor,
        string $remarks,
    ): OperationalPlan {
        return DB::transaction(function () use ($operationalPlan, $actor, $remarks): OperationalPlan {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('reopen', $lockedOperationalPlan);

            $fromStatus = $lockedOperationalPlan->status;
            $lockedOperationalPlan->update([
                'status' => OperationalPlanStatus::Returned,
                'returned_by' => $actor->id,
                'returned_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->recordStatus->handle(
                $lockedOperationalPlan,
                $actor,
                $fromStatus,
                OperationalPlanStatus::Returned,
                $remarks,
            );

            return $lockedOperationalPlan->refresh();
        });
    }
}
