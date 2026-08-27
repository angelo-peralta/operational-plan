<?php

namespace App\Http\Requests;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use App\Models\ReportingPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAcademicYearRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $academicYear = $this->route('academic_year');

        return $academicYear instanceof AcademicYear
            && ($this->user()?->can('update', $academicYear) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $academicYear = $this->route('academic_year');
        $academicYearId = $academicYear instanceof AcademicYear ? $academicYear->id : null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('academic_years', 'name')->ignore($academicYearId),
            ],
            'start_year' => ['required', 'integer', 'min:2000', 'max:9998'],
            'end_year' => [
                'required',
                'integer',
                'min:2001',
                'max:9999',
                Rule::unique('academic_years', 'end_year')
                    ->where('start_year', $this->integer('start_year'))
                    ->ignore($academicYearId),
            ],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after:starts_on'],
            'status' => ['required', 'string', Rule::enum(AcademicYearStatus::class)],
            'is_current' => ['required', 'boolean'],
        ];
    }

    /**
     * Get the validated Academic Year attributes.
     *
     * @return array{name: string, start_year: int, end_year: int, starts_on: string|null, ends_on: string|null, status: string, is_current: bool}
     */
    public function validatedData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'start_year' => $this->integer('start_year'),
            'end_year' => $this->integer('end_year'),
            'starts_on' => $this->filled('starts_on') ? $this->string('starts_on')->toString() : null,
            'ends_on' => $this->filled('ends_on') ? $this->string('ends_on')->toString() : null,
            'status' => $this->string('status')->toString(),
            'is_current' => $this->boolean('is_current'),
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
                if ($this->integer('end_year') !== $this->integer('start_year') + 1) {
                    $validator->errors()->add('end_year', __('The end year must be one year after the start year.'));
                }

                if ($this->boolean('is_current') && $this->input('status') !== AcademicYearStatus::Open->value) {
                    $validator->errors()->add('is_current', __('Only an open academic year may be current.'));
                }

                if (
                    $this->filled('starts_on')
                    && ! $validator->errors()->has('starts_on')
                    && ! $validator->errors()->has('start_year')
                    && $this->date('starts_on')?->year !== $this->integer('start_year')
                ) {
                    $validator->errors()->add('starts_on', __('The start date must fall within the start year.'));
                }

                if (
                    $this->filled('ends_on')
                    && ! $validator->errors()->has('ends_on')
                    && ! $validator->errors()->has('end_year')
                    && $this->date('ends_on')?->year !== $this->integer('end_year')
                ) {
                    $validator->errors()->add('ends_on', __('The end date must fall within the end year.'));
                }

                $this->validateReportingPeriodDates($validator);
            },
        ];
    }

    /**
     * Prevent an Academic Year update from excluding existing reporting-period dates.
     */
    private function validateReportingPeriodDates(Validator $validator): void
    {
        $academicYear = $this->route('academic_year');

        if (
            ! $academicYear instanceof AcademicYear
            || $validator->errors()->hasAny(['start_year', 'end_year', 'starts_on', 'ends_on'])
        ) {
            return;
        }

        $startsOn = $this->date('starts_on')
            ?? now()->setDate($this->integer('start_year'), 1, 1)->startOfDay();
        $endsOn = $this->date('ends_on')
            ?? now()->setDate($this->integer('end_year'), 12, 31)->startOfDay();

        $hasPeriodOutsideDates = $academicYear->reportingPeriods()
            ->get(['starts_on', 'ends_on'])
            ->contains(function (ReportingPeriod $reportingPeriod) use ($startsOn, $endsOn): bool {
                return ($reportingPeriod->starts_on !== null && (
                    $reportingPeriod->starts_on->lessThan($startsOn)
                    || $reportingPeriod->starts_on->greaterThan($endsOn)
                )) || ($reportingPeriod->ends_on !== null && (
                    $reportingPeriod->ends_on->lessThan($startsOn)
                    || $reportingPeriod->ends_on->greaterThan($endsOn)
                ));
            });

        if ($hasPeriodOutsideDates) {
            $validator->errors()->add(
                'starts_on',
                __('The Academic Year dates must include all existing reporting-period dates.'),
            );
        }
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        $data = [
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
        ];

        if ($this->has('is_current')) {
            $data['is_current'] = in_array($status, [AcademicYearStatus::Closed->value, AcademicYearStatus::Archived->value], true)
                ? false
                : $this->boolean('is_current');
        }

        $this->merge($data);
    }
}
