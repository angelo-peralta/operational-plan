<?php

namespace App\Http\Requests;

use App\Http\Middleware\ResolveAcademicYearContext;
use App\Models\AcademicYear;
use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\PlanItem;
use App\Models\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;

abstract class MonitoringRequest extends FormRequest
{
    private ?AcademicYear $resolvedAcademicYear = null;

    private ?ReportingPeriod $resolvedReportingPeriod = null;

    private ?PlanItem $resolvedPlanItem = null;

    private ?Accomplishment $resolvedAccomplishment = null;

    private ?Evidence $resolvedEvidence = null;

    public function selectedAcademicYear(): AcademicYear
    {
        if ($this->resolvedAcademicYear !== null) {
            return $this->resolvedAcademicYear;
        }

        $academicYear = $this->attributes->get(ResolveAcademicYearContext::ATTRIBUTE_KEY);

        abort_unless($academicYear instanceof AcademicYear, 404);

        return $this->resolvedAcademicYear = $academicYear;
    }

    public function reportingPeriod(): ReportingPeriod
    {
        if ($this->resolvedReportingPeriod !== null) {
            return $this->resolvedReportingPeriod;
        }

        $reportingPeriod = ReportingPeriod::query()
            ->whereKey($this->routeIdentifier('reporting_period'))
            ->whereBelongsTo($this->selectedAcademicYear())
            ->firstOrFail();
        $reportingPeriod->setRelation('academicYear', $this->selectedAcademicYear());

        return $this->resolvedReportingPeriod = $reportingPeriod;
    }

    public function planItem(): PlanItem
    {
        if ($this->resolvedPlanItem !== null) {
            return $this->resolvedPlanItem;
        }

        $user = $this->user();
        $query = PlanItem::query()
            ->with('keyResultArea.operationalPlan.academicYear')
            ->whereKey($this->routeIdentifier('plan_item'))
            ->whereHas(
                'keyResultArea.operationalPlan',
                function (Builder $query): void {
                    $query->whereBelongsTo($this->selectedAcademicYear());
                },
            );

        if ($user?->isDepartmentUser()) {
            abort_if($user->department_id === null, 404);
            $query->whereHas(
                'keyResultArea.operationalPlan',
                fn (Builder $query): Builder => $query->where('department_id', $user->department_id),
            );
        }

        return $this->resolvedPlanItem = $query->firstOrFail();
    }

    public function accomplishment(): Accomplishment
    {
        if ($this->resolvedAccomplishment !== null) {
            return $this->resolvedAccomplishment;
        }

        $accomplishment = Accomplishment::query()
            ->whereKey($this->routeIdentifier('accomplishment'))
            ->whereBelongsTo($this->planItem())
            ->whereBelongsTo($this->reportingPeriod())
            ->firstOrFail();
        $accomplishment->setRelation('planItem', $this->planItem());
        $accomplishment->setRelation('reportingPeriod', $this->reportingPeriod());

        return $this->resolvedAccomplishment = $accomplishment;
    }

    public function evidence(): Evidence
    {
        if ($this->resolvedEvidence !== null) {
            return $this->resolvedEvidence;
        }

        $evidence = Evidence::query()
            ->with('accomplishment.planItem.keyResultArea.operationalPlan.academicYear')
            ->whereKey($this->routeIdentifier('evidence'))
            ->whereBelongsTo($this->accomplishment())
            ->firstOrFail();
        $evidence->setRelation('accomplishment', $this->accomplishment());

        return $this->resolvedEvidence = $evidence;
    }

    private function routeIdentifier(string $parameter): int
    {
        $value = $this->route($parameter);

        if ($value instanceof Accomplishment || $value instanceof Evidence || $value instanceof PlanItem || $value instanceof ReportingPeriod) {
            return (int) $value->getKey();
        }

        $isPositiveInteger = is_string($value)
            && ctype_digit($value)
            && (int) $value > 0;

        abort_unless($isPositiveInteger, 404);

        return (int) $value;
    }
}
