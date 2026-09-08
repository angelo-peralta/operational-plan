<?php

namespace App\Actions\Remarks;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Enums\RemarkStatus;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateRemark
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /** @param array{field_key: string|null, reporting_period_id: int|null, category: string, content: string} $data */
    public function handle(int $operationalPlanId, Model $target, User $actor, array $data): Remark
    {
        return DB::transaction(function () use ($operationalPlanId, $target, $actor, $data): Remark {
            $plan = $this->lockOperationalPlan->handle($operationalPlanId);
            Gate::forUser($actor)->authorize('create', [Remark::class, $plan]);

            $remark = Remark::query()->create([
                ...$data,
                'academic_year_id' => $plan->academic_year_id,
                'operational_plan_id' => $plan->id,
                'author_id' => $actor->id,
                'remarkable_type' => $target->getMorphClass(),
                'remarkable_id' => $target->getKey(),
                'status' => RemarkStatus::Open,
            ]);
            $remark->statusHistories()->create([
                'actor_id' => $actor->id,
                'from_status' => null,
                'to_status' => RemarkStatus::Open,
                'created_at' => now(),
            ]);

            return $remark;
        });
    }
}
