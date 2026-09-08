<?php

namespace App\Actions\Remarks;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Enums\RemarkStatus;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ChangeRemarkStatus
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    public function handle(Remark $remark, User $actor, RemarkStatus $status, ?string $note): Remark
    {
        return DB::transaction(function () use ($remark, $actor, $status, $note): Remark {
            $plan = $this->lockOperationalPlan->handle($remark->operational_plan_id);
            $lockedRemark = Remark::query()->whereKey($remark->id)->whereBelongsTo($plan)->lockForUpdate()->firstOrFail();
            $lockedRemark->setRelation('operationalPlan', $plan);
            $ability = $status === RemarkStatus::Addressed ? 'address' : 'resolve';
            Gate::forUser($actor)->authorize($ability, $lockedRemark);

            $fromStatus = $lockedRemark->status;
            $attributes = ['status' => $status];
            if ($status === RemarkStatus::Addressed) {
                $attributes += ['addressed_by' => $actor->id, 'addressed_at' => now()];
            } else {
                $attributes += ['resolved_by' => $actor->id, 'resolved_at' => now()];
            }
            $lockedRemark->update($attributes);
            $lockedRemark->statusHistories()->create([
                'actor_id' => $actor->id,
                'from_status' => $fromStatus,
                'to_status' => $status,
                'note' => $note,
                'created_at' => now(),
            ]);

            return $lockedRemark->refresh();
        });
    }
}
