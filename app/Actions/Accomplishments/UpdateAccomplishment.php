<?php

namespace App\Actions\Accomplishments;

use App\Models\Accomplishment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class UpdateAccomplishment
{
    public function __construct(
        private CalculateAccomplishmentPercentage $calculatePercentage,
        private LockAccomplishmentForMutation $lockAccomplishment,
    ) {}

    /** @param array{reported_value: string|null, accomplishment_text: string|null} $data */
    public function handle(Accomplishment $accomplishment, User $actor, array $data): Accomplishment
    {
        return DB::transaction(function () use ($accomplishment, $actor, $data): Accomplishment {
            $context = $this->lockAccomplishment->handle(
                $accomplishment->plan_item_id,
                $accomplishment->reporting_period_id,
                $accomplishment->id,
            );
            $lockedAccomplishment = $context['accomplishment'];
            assert($lockedAccomplishment instanceof Accomplishment);

            Gate::forUser($actor)->authorize('update', $lockedAccomplishment);

            $lockedAccomplishment->update([
                'reported_value' => $data['reported_value'],
                'accomplishment_text' => $data['accomplishment_text'],
                'percentage_accomplished' => $this->calculatePercentage->handle(
                    $context['planItem'],
                    $data['reported_value'],
                ),
            ]);

            return $lockedAccomplishment;
        });
    }
}
