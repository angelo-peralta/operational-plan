<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

abstract class OperationalPlanFieldsRequest extends PlanningRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accountable_user_id' => ['nullable', 'integer', Rule::exists(User::class, 'id')],
            'accountable_name' => [
                'nullable',
                'required_without_all:accountable_user_id,accountable_position',
                'string',
                'max:255',
            ],
            'accountable_position' => ['nullable', 'string', 'max:255'],
            'goal' => ['required', 'string', 'max:20000'],
            'academic_year_id' => ['prohibited'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
            'submitted_by' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'approved_by' => ['prohibited'],
            'approved_at' => ['prohibited'],
            'returned_by' => ['prohibited'],
            'returned_at' => ['prohibited'],
            'closed_by' => ['prohibited'],
            'closed_at' => ['prohibited'],
        ];
    }

    /** @return array{accountable_user_id: int|null, accountable_name: string|null, accountable_position: string|null, goal: string} */
    public function validatedPlanData(): array
    {
        return [
            'accountable_user_id' => $this->filled('accountable_user_id')
                ? $this->integer('accountable_user_id')
                : null,
            'accountable_name' => $this->filled('accountable_name')
                ? $this->string('accountable_name')->toString()
                : null,
            'accountable_position' => $this->filled('accountable_position')
                ? $this->string('accountable_position')->toString()
                : null,
            'goal' => $this->string('goal')->toString(),
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('accountable_user_id') || $validator->errors()->has('accountable_user_id')) {
                    return;
                }

                $departmentId = $this->planningDepartmentId();

                if ($departmentId === null || ! User::query()
                    ->whereKey($this->integer('accountable_user_id'))
                    ->where('department_id', $departmentId)
                    ->exists()) {
                    $validator->errors()->add(
                        'accountable_user_id',
                        __('The accountable user must belong to the plan department.'),
                    );
                }
            },
        ];
    }

    abstract protected function planningDepartmentId(): ?int;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'accountable_name' => $this->normalizedString('accountable_name'),
            'accountable_position' => $this->normalizedString('accountable_position'),
            'goal' => is_string($this->input('goal')) ? trim($this->input('goal')) : $this->input('goal'),
        ]);
    }

    private function normalizedString(string $field): mixed
    {
        $value = $this->input($field);

        if (! is_string($value)) {
            return $value;
        }

        $normalized = Str::squish($value);

        return $normalized === '' ? null : $normalized;
    }
}
