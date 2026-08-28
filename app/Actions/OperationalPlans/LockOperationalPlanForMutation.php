<?php

namespace App\Actions\OperationalPlans;

use App\Models\AcademicYear;
use App\Models\OperationalPlan;

class LockOperationalPlanForMutation
{
    public function handle(int $operationalPlanId): OperationalPlan
    {
        $operationalPlanReference = OperationalPlan::query()
            ->select(['id', 'academic_year_id'])
            ->findOrFail($operationalPlanId);

        $academicYear = AcademicYear::query()
            ->whereKey($operationalPlanReference->academic_year_id)
            ->lockForUpdate()
            ->firstOrFail();
        $operationalPlan = OperationalPlan::query()
            ->whereKey($operationalPlanId)
            ->lockForUpdate()
            ->firstOrFail();

        abort_unless($operationalPlan->academic_year_id === $academicYear->id, 404);

        $operationalPlan->setRelation('academicYear', $academicYear);

        return $operationalPlan;
    }
}
