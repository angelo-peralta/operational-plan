<?php

namespace App\Actions\OperationalPlans;

use App\Actions\OrganizationalUnits\ResolveOrganizationalScope;
use App\Enums\OperationalPlanStatus;
use App\Models\AcademicYear;
use App\Models\KeyResultAreaTemplate;
use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateOperationalPlan
{
    public function __construct(private RecordOperationalPlanStatus $recordStatus, private ResolveOrganizationalScope $scope) {}

    /**
     * @param  array{accountable_user_id: int|null, accountable_name: string|null, accountable_position: string|null, goal: string}  $data
     */
    public function handle(
        AcademicYear $academicYear,
        OrganizationalUnit $organizationalUnit,
        User $actor,
        array $data,
    ): OperationalPlan {
        return DB::transaction(function () use ($academicYear, $organizationalUnit, $actor, $data): OperationalPlan {
            $lockedAcademicYear = AcademicYear::query()
                ->whereKey($academicYear->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedOrganizationalUnit = OrganizationalUnit::query()
                ->whereKey($organizationalUnit->id)
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($actor)->authorize('create', [OperationalPlan::class, $lockedAcademicYear]);

            if (! $this->scope->canManageUnit($actor, $lockedOrganizationalUnit->id)) {
                throw ValidationException::withMessages([
                    'organizational_unit_id' => __('The selected organizational unit is not available for this Operational Plan.'),
                ]);
            }

            if (OperationalPlan::query()
                ->whereBelongsTo($lockedAcademicYear)
                ->where('organizational_unit_id', $lockedOrganizationalUnit->id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'organizational_unit_id' => __('This organizational unit already has an Operational Plan for the selected Academic Year.'),
                ]);
            }

            $keyResultAreaTemplates = KeyResultAreaTemplate::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            if ($keyResultAreaTemplates->isEmpty()) {
                throw ValidationException::withMessages([
                    'organizational_unit_id' => __('No active global KRA template is available. Contact the system administrator.'),
                ]);
            }

            $operationalPlan = OperationalPlan::query()->create([
                ...$data,
                'academic_year_id' => $lockedAcademicYear->id,
                'organizational_unit_id' => $lockedOrganizationalUnit->id,
                'status' => OperationalPlanStatus::Draft,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $operationalPlan->keyResultAreas()->createMany(
                $keyResultAreaTemplates->map(fn (KeyResultAreaTemplate $template): array => [
                    'code' => $template->code,
                    'name' => $template->name,
                    'description' => $template->description,
                    'sort_order' => $template->sort_order,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ])->all(),
            );

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
