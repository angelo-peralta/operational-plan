<?php

namespace App\Actions\Accomplishments;

use App\Enums\AccomplishmentStatus;
use App\Models\Accomplishment;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateAccomplishment
{
    public function __construct(
        private CalculateAccomplishmentPercentage $calculatePercentage,
        private LockAccomplishmentForMutation $lockAccomplishment,
    ) {}

    /** @param array{reported_value: string|null, accomplishment_text: string|null} $data */
    public function handle(
        PlanItem $planItem,
        ReportingPeriod $reportingPeriod,
        User $actor,
        array $data,
    ): Accomplishment {
        return DB::transaction(function () use ($planItem, $reportingPeriod, $actor, $data): Accomplishment {
            $context = $this->lockAccomplishment->handle($planItem->id, $reportingPeriod->id);

            Gate::forUser($actor)->authorize('create', [
                Accomplishment::class,
                $context['planItem'],
                $context['reportingPeriod'],
            ]);

            if ($context['accomplishment'] !== null) {
                throw ValidationException::withMessages([
                    'accomplishment' => __('An accomplishment already exists for this Plan Item and reporting period.'),
                ]);
            }

            $accomplishment = Accomplishment::query()->create([
                'plan_item_id' => $context['planItem']->id,
                'reporting_period_id' => $context['reportingPeriod']->id,
                'reported_value' => $data['reported_value'],
                'accomplishment_text' => $data['accomplishment_text'],
                'percentage_accomplished' => $this->calculatePercentage->handle(
                    $context['planItem'],
                    $data['reported_value'],
                ),
                'status' => AccomplishmentStatus::Draft,
            ]);
            $accomplishment->setRelation('planItem', $context['planItem']);
            $accomplishment->setRelation('reportingPeriod', $context['reportingPeriod']);

            return $accomplishment;
        });
    }
}
