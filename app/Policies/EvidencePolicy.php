<?php

namespace App\Policies;

use App\Enums\AccomplishmentStatus;
use App\Enums\OperationalPlanStatus;
use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\User;

class EvidencePolicy
{
    public function view(User $user, Evidence $evidence): bool
    {
        return $user->can('view', $evidence->accomplishment);
    }

    public function create(User $user, Accomplishment $accomplishment): bool
    {
        if (! $user->isDepartmentUser()) {
            return false;
        }

        $operationalPlan = $accomplishment->planItem->keyResultArea->operationalPlan;

        return $user->department_id !== null
            && $user->department_id === $operationalPlan->department_id
            && $operationalPlan->academic_year_id === $accomplishment->reportingPeriod->academic_year_id
            && $operationalPlan->status === OperationalPlanStatus::Approved
            && $accomplishment->reportingPeriod->acceptsSubmissions()
            && in_array($accomplishment->status, [
                AccomplishmentStatus::Draft,
                AccomplishmentStatus::Returned,
            ], true);
    }

    public function delete(User $user, Evidence $evidence): bool
    {
        return false;
    }
}
