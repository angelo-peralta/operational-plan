<?php

namespace App\Http\Requests;

use App\Enums\RemarkCategory;
use App\Enums\RemarkField;
use App\Models\Accomplishment;
use App\Models\Evaluation;
use App\Models\Evidence;
use App\Models\KeyResultArea;
use App\Models\OperationalPlan;
use App\Models\PlanItem;
use App\Models\Remark;
use App\Models\ReportingPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRemarkRequest extends PlanningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Remark::class, $this->operationalPlan()]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'target_type' => ['required', Rule::in(['operational_plan', 'key_result_area', 'plan_item', 'accomplishment', 'evidence', 'evaluation'])],
            'target_id' => ['required', 'integer'],
            'field_key' => ['nullable', Rule::enum(RemarkField::class)],
            'reporting_period_id' => ['nullable', 'integer', Rule::exists(ReportingPeriod::class, 'id')],
            'category' => ['required', Rule::enum(RemarkCategory::class)],
            'content' => ['required', 'string', 'max:10000'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $target = $this->target();
            if ($target === null || $this->targetOperationalPlanId($target) !== $this->operationalPlan()->id) {
                $validator->errors()->add('target_id', __('The selected remark target is unavailable.'));
            }

            if ($target !== null && $this->filled('field_key') && ! in_array($this->string('field_key')->toString(), $this->allowedFields($target), true)) {
                $validator->errors()->add('field_key', __('The selected field does not belong to this remark target.'));
            }

            if ($this->filled('reporting_period_id')) {
                $reportingPeriodId = $this->integer('reporting_period_id');
                $belongsToAcademicYear = ReportingPeriod::query()
                    ->whereKey($reportingPeriodId)
                    ->where('academic_year_id', $this->operationalPlan()->academic_year_id)
                    ->exists();
                $targetReportingPeriodId = $target ? $this->targetReportingPeriodId($target) : null;

                if (! $belongsToAcademicYear || ($targetReportingPeriodId !== null && $targetReportingPeriodId !== $reportingPeriodId)) {
                    $validator->errors()->add('reporting_period_id', __('The selected reporting period does not belong to this remark target.'));
                }
            }
        }];
    }

    public function target(): ?Model
    {
        return match ($this->string('target_type')->toString()) {
            'operational_plan' => OperationalPlan::query()->find($this->integer('target_id')),
            'key_result_area' => KeyResultArea::query()->find($this->integer('target_id')),
            'plan_item' => PlanItem::query()->find($this->integer('target_id')),
            'accomplishment' => Accomplishment::query()->find($this->integer('target_id')),
            'evidence' => Evidence::query()->find($this->integer('target_id')),
            'evaluation' => Evaluation::query()->find($this->integer('target_id')),
            default => null,
        };
    }

    /** @return array{field_key: string|null, reporting_period_id: int|null, category: string, content: string} */
    public function remarkData(): array
    {
        $target = $this->target();
        $targetReportingPeriodId = $target ? $this->targetReportingPeriodId($target) : null;

        return [
            'field_key' => $this->filled('field_key') ? $this->string('field_key')->toString() : null,
            'reporting_period_id' => $targetReportingPeriodId
                ?? ($this->filled('reporting_period_id') ? $this->integer('reporting_period_id') : null),
            'category' => $this->string('category')->toString(),
            'content' => $this->string('content')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('content'))) {
            $this->merge(['content' => Str::of($this->input('content'))->trim()->toString()]);
        }
    }

    private function targetOperationalPlanId(Model $target): int
    {
        return match (true) {
            $target instanceof OperationalPlan => $target->id,
            $target instanceof KeyResultArea => $target->operational_plan_id,
            $target instanceof PlanItem => $target->keyResultArea->operational_plan_id,
            $target instanceof Accomplishment => $target->planItem->keyResultArea->operational_plan_id,
            $target instanceof Evidence => $target->accomplishment->planItem->keyResultArea->operational_plan_id,
            $target instanceof Evaluation => $target->submission->accomplishment->planItem->keyResultArea->operational_plan_id,
            default => 0,
        };
    }

    /** @return list<string> */
    private function allowedFields(Model $target): array
    {
        return match (true) {
            $target instanceof OperationalPlan => ['accountable', 'goal'],
            $target instanceof KeyResultArea => ['kra_name'],
            $target instanceof PlanItem => ['objective', 'strategy', 'kpi_target_text', 'co_accountables', 'resources_needed', 'documentary_evidence_requirements'],
            $target instanceof Accomplishment => ['accomplishment_text'],
            $target instanceof Evidence => ['evidence'],
            $target instanceof Evaluation => ['evaluation'],
            default => [],
        };
    }

    private function targetReportingPeriodId(Model $target): ?int
    {
        return match (true) {
            $target instanceof Accomplishment => $target->reporting_period_id,
            $target instanceof Evidence => $target->accomplishment->reporting_period_id,
            $target instanceof Evaluation => $target->submission->accomplishment->reporting_period_id,
            default => null,
        };
    }
}
