<?php

namespace App\Policies;

use App\Enums\AcademicYearStatus;
use App\Models\ReportingPeriod;
use App\Models\User;

class ReportingPeriodPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ReportingPeriod $reportingPeriod): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isSystemAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ReportingPeriod $reportingPeriod): bool
    {
        return $user->isSystemAdmin()
            && in_array($reportingPeriod->academicYear->status, [
                AcademicYearStatus::Draft,
                AcademicYearStatus::Open,
            ], true);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ReportingPeriod $reportingPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ReportingPeriod $reportingPeriod): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ReportingPeriod $reportingPeriod): bool
    {
        return false;
    }
}
