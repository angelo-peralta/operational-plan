<?php

namespace App\Actions\OperationalPlans;

use App\Enums\OperationalPlanStatus;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ApproveOperationalPlan
{
    public function __construct(
        private RecordOperationalPlanStatus $recordStatus,
        private LockOperationalPlanForMutation $lockOperationalPlan,
    ) {}

    public function handle(OperationalPlan $operationalPlan, User $actor): OperationalPlan
    {
        return DB::transaction(function () use ($operationalPlan, $actor): OperationalPlan {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('approve', $lockedOperationalPlan);

            $fromStatus = $lockedOperationalPlan->status;
            $lockedOperationalPlan->update([
                'status' => OperationalPlanStatus::Approved,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'updated_by' => $actor->id,
            ]);

            $this->recordStatus->handle(
                $lockedOperationalPlan,
                $actor,
                $fromStatus,
                OperationalPlanStatus::Approved,
            );

            return $lockedOperationalPlan->refresh();
        });
    }
}
