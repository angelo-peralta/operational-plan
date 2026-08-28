<?php

namespace App\Actions\Accomplishments;

use App\Actions\OperationalPlans\LockOperationalPlanForMutation;
use App\Models\Accomplishment;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;

class LockAccomplishmentForMutation
{
    public function __construct(private LockOperationalPlanForMutation $lockOperationalPlan) {}

    /**
     * @return array{
     *     operationalPlan: OperationalPlan,
     *     planItem: PlanItem,
     *     reportingPeriod: ReportingPeriod,
     *     accomplishment: Accomplishment|null
     * }
     */
    public function handle(
        int $planItemId,
        int $reportingPeriodId,
        ?int $accomplishmentId = null,
    ): array {
        $planItemReference = PlanItem::query()
            ->select(['id', 'key_result_area_id'])
            ->findOrFail($planItemId);
        $keyResultAreaReference = KeyResultArea::query()
            ->select(['id', 'operational_plan_id'])
            ->findOrFail($planItemReference->key_result_area_id);
        $operationalPlan = $this->lockOperationalPlan->handle($keyResultAreaReference->operational_plan_id);
        $reportingPeriod = ReportingPeriod::query()
            ->whereKey($reportingPeriodId)
            ->whereBelongsTo($operationalPlan->academicYear)
            ->lockForUpdate()
            ->firstOrFail();
        $reportingPeriod->setRelation('academicYear', $operationalPlan->academicYear);
        $keyResultArea = KeyResultArea::query()
            ->whereKey($keyResultAreaReference->id)
            ->whereBelongsTo($operationalPlan)
            ->lockForUpdate()
            ->firstOrFail();
        $keyResultArea->setRelation('operationalPlan', $operationalPlan);
        $planItem = PlanItem::query()
            ->whereKey($planItemId)
            ->whereBelongsTo($keyResultArea)
            ->lockForUpdate()
            ->firstOrFail();
        $planItem->setRelation('keyResultArea', $keyResultArea);

        $accomplishmentQuery = Accomplishment::query()
            ->whereBelongsTo($planItem)
            ->whereBelongsTo($reportingPeriod)
            ->lockForUpdate();

        $accomplishment = $accomplishmentId === null
            ? $accomplishmentQuery->first()
            : $accomplishmentQuery->whereKey($accomplishmentId)->firstOrFail();

        if ($accomplishment !== null) {
            $accomplishment->setRelation('planItem', $planItem);
            $accomplishment->setRelation('reportingPeriod', $reportingPeriod);
        }

        return [
            'operationalPlan' => $operationalPlan,
            'planItem' => $planItem,
            'reportingPeriod' => $reportingPeriod,
            'accomplishment' => $accomplishment,
        ];
    }
}
