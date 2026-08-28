<?php

namespace App\Actions\OperationalPlans;

use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\Department;
use App\Models\OperationalPlan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateOperationalPlan
{
    public function __construct(private RecordOperationalPlanStatus $recordStatus) {}

    /**
     * @param  array{accountable_user_id: int|null, accountable_name: string|null, accountable_position: string|null, goal: string}  $data
     */
    public function handle(
        AcademicYear $academicYear,
        Department $department,
        User $actor,
        array $data,
    ): OperationalPlan {
        return DB::transaction(function () use ($academicYear, $department, $actor, $data): OperationalPlan {
            $lockedAcademicYear = AcademicYear::query()
                ->whereKey($academicYear->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedDepartment = Department::query()
                ->whereKey($department->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('create', [OperationalPlan::class, $lockedAcademicYear]);

            if (! $lockedDepartment->isActive() || ($actor->isDepartmentUser() && $actor->department_id !== $lockedDepartment->id)) {
                throw ValidationException::withMessages([
                    'department_id' => __('The selected department is not available for this Operational Plan.'),
                ]);
            }

            if (OperationalPlan::query()
                ->whereBelongsTo($lockedAcademicYear)
                ->whereBelongsTo($lockedDepartment)
                ->exists()) {
                throw ValidationException::withMessages([
                    'department_id' => __('This department already has an Operational Plan for the selected Academic Year.'),
                ]);
            }

            $operationalPlan = OperationalPlan::query()->create([
                ...$data,
                'academic_year_id' => $lockedAcademicYear->id,
                'department_id' => $lockedDepartment->id,
                'status' => OperationalPlanStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->recordStatus->handle(
                $operationalPlan,
                $actor,
                null,
                OperationalPlanStatus::Draft,
            );

            return $operationalPlan->refresh();
        });
    }
}
