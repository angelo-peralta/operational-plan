<?php

namespace App\Http\Requests;

use App\Enums\ReportingPeriodStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateReportingPeriodRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');
        $reportingPeriod = $this->route('reporting_period');

        if (! $academicYear instanceof AcademicYear || ! $reportingPeriod instanceof ReportingPeriod) {
            return false;
        }

        abort_if($reportingPeriod->academic_year_id !== $academicYear->id, 404);

        return $this->user()?->can('update', $reportingPeriod) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicYear = $this->route('academic_year');
        $reportingPeriod = $this->route('reporting_period');
        $academicYearId = $academicYear instanceof AcademicYear ? $academicYear->id : null;
        $reportingPeriodId = $reportingPeriod instanceof ReportingPeriod ? $reportingPeriod->id : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('reporting_periods', 'code')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($reportingPeriodId),
            ],
            'sequence' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('reporting_periods', 'sequence')
                    ->where('academic_year_id', $academicYearId)
                    ->ignore($reportingPeriodId),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on'],
            'status' => ['required', 'string', Rule::enum(ReportingPeriodStatus::class)],
        ];
    }

    /**
     * Get the after validation callables for the request.
     *
     * @return array<callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $academicYear = $this->route('academic_year');

                if (! $academicYear instanceof AcademicYear) {
                    return;
                }

                $academicYearStartsOn = $academicYear->starts_on
                    ?? now()->setDate($academicYear->start_year, 1, 1)->startOfDay();
                $academicYearEndsOn = $academicYear->ends_on
                    ?? now()->setDate($academicYear->end_year, 12, 31)->startOfDay();

                foreach (['starts_on', 'ends_on'] as $field) {
                    if (! $this->filled($field) || $validator->errors()->has($field)) {
                        continue;
                    }

                    $date = $this->date($field);

                    if ($date?->lessThan($academicYearStartsOn) || $date?->greaterThan($academicYearEndsOn)) {
                        $validator->errors()->add(
                            $field,
                            __('The reporting-period date must fall within the Academic Year.'),
                        );
                    }
                }
            },
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
            'code' => is_string($this->input('code')) ? Str::snake($this->input('code')) : $this->input('code'),
        ]);
    }
}
