<?php

namespace App\Http\Requests;

use App\Enums\AcademicYearStatus;
use App\Models\AcademicYear;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAcademicYearRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', AcademicYear::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('academic_years', 'name')],
            'start_year' => ['required', 'integer', 'min:2000', 'max:9998'],
            'end_year' => [
                'required',
                'integer',
                'min:2001',
                'max:9999',
                Rule::unique('academic_years', 'end_year')
                    ->where('start_year', $this->integer('start_year')),
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
            },
        ];
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
