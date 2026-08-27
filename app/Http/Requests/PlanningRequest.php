<?php

namespace App\Http\Requests;

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use Illuminate\Foundation\Http\FormRequest;

abstract class PlanningRequest extends FormRequest
{
    private ?AcademicYear $resolvedAcademicYear = null;

    private ?OperationalPlan $resolvedOperationalPlan = null;

    private ?KeyResultArea $resolvedKeyResultArea = null;

    private ?PlanItem $resolvedPlanItem = null;

    public function selectedAcademicYear(): AcademicYear
    {
        if ($this->resolvedAcademicYear !== null) {
            return $this->resolvedAcademicYear;
        }

        $academicYear = $this->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);

        abort_unless($academicYear instanceof AcademicYear, 404);

        return $this->resolvedAcademicYear = $academicYear;
    }

    public function operationalPlan(): OperationalPlan
    {
        if ($this->resolvedOperationalPlan !== null) {
            return $this->resolvedOperationalPlan;
        }

        $query = OperationalPlan::query()
            ->whereKey($this->routeIdentifier('operational_plan'))
            ->whereBelongsTo($this->selectedAcademicYear());
        $user = $this->user();

        if ($user?->isDepartmentUser()) {
            abort_if($user->department_id === null, 404);
            $query->where('department_id', $user->department_id);
        }

        return $this->resolvedOperationalPlan = $query->firstOrFail();
    }

    public function keyResultArea(): KeyResultArea
    {
        if ($this->resolvedKeyResultArea !== null) {
            return $this->resolvedKeyResultArea;
        }

        return $this->resolvedKeyResultArea = KeyResultArea::query()
            ->whereKey($this->routeIdentifier('key_result_area'))
            ->whereBelongsTo($this->operationalPlan())
            ->firstOrFail();
    }

    public function planItem(): PlanItem
    {
        if ($this->resolvedPlanItem !== null) {
            return $this->resolvedPlanItem;
        }

        return $this->resolvedPlanItem = PlanItem::query()
            ->whereKey($this->routeIdentifier('plan_item'))
            ->whereBelongsTo($this->keyResultArea())
            ->firstOrFail();
    }

    private function routeIdentifier(string $parameter): int
    {
        $value = $this->route($parameter);

        if ($value instanceof AcademicYear || $value instanceof OperationalPlan || $value instanceof KeyResultArea || $value instanceof PlanItem) {
            return (int) $value->getKey();
        }

        abort_unless(is_numeric($value) && (int) $value > 0, 404);

        return (int) $value;
    }
}
