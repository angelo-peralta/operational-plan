<?php

namespace App\Actions\KeyResultAreas;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class CreateKeyResultArea
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @param  array{code: string|null, name: string, description: string|null}  $data
     */
    public function handle(OperationalPlan $operationalPlan, User $actor, array $data): KeyResultArea
    {
        return DB::transaction(function () use ($operationalPlan, $actor, $data): KeyResultArea {
            $lockedOperationalPlan = $this->lockOperationalPlan->handle($operationalPlan->id);

            Gate::forUser($actor)->authorize('create', [KeyResultArea::class, $lockedOperationalPlan]);

            $sortOrder = ((int) $lockedOperationalPlan->keyResultAreas()->max('sort_order')) + 1;

            return $lockedOperationalPlan->keyResultAreas()->create([
                ...$data,
                'sort_order' => $sortOrder,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
        });
    }
}
