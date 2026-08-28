<?php

namespace App\Http\Requests;

use App\Enums\TargetOperator;
use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

abstract class PlanItemFieldsRequest extends PlanningRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'objective' => ['required', 'string', 'max:20000'],
            'strategy' => ['nullable', 'string', 'max:20000'],
            'kpi_target_text' => ['required', 'string', 'max:20000'],
            'target_value' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'target_unit' => ['nullable', 'string', 'max:100'],
            'target_operator' => ['nullable', 'string', Rule::enum(TargetOperator::class)],
            'target_frequency' => ['nullable', 'string', 'max:100'],
            'resources_needed' => ['nullable', 'string', 'max:20000'],
            'documentary_evidence_requirements' => ['nullable', 'array', 'max:50'],
            'documentary_evidence_requirements.*' => ['required', 'string', 'distinct', 'max:500'],
            'co_accountable_department_ids' => ['nullable', 'array', 'max:50'],
            'co_accountable_department_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(Department::class, 'id'),
            ],
            'manual_co_accountable_units' => ['nullable', 'array', 'max:50'],
            'manual_co_accountable_units.*' => ['required', 'string', 'distinct', 'max:100'],
            'key_result_area_id' => ['prohibited'],
            'sort_order' => ['prohibited'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
        ];
    }

    /**
     * @return array{
     *     objective: string,
     *     strategy: string|null,
     *     kpi_target_text: string,
     *     target_value: string|null,
     *     target_unit: string|null,
     *     target_operator: string|null,
     *     target_frequency: string|null,
     *     resources_needed: string|null,
     *     documentary_evidence_requirements: list<string>,
     *     manual_co_accountable_units: list<string>
     * }
     */
    public function validatedData(): array
    {
        return [
            'objective' => $this->string('objective')->toString(),
            'strategy' => $this->filled('strategy') ? $this->string('strategy')->toString() : null,
            'kpi_target_text' => $this->string('kpi_target_text')->toString(),
            'target_value' => $this->filled('target_value') ? $this->string('target_value')->toString() : null,
            'target_unit' => $this->filled('target_unit') ? $this->string('target_unit')->toString() : null,
            'target_operator' => $this->filled('target_operator') ? $this->string('target_operator')->toString() : null,
            'target_frequency' => $this->filled('target_frequency') ? $this->string('target_frequency')->toString() : null,
            'resources_needed' => $this->filled('resources_needed') ? $this->string('resources_needed')->toString() : null,
            'documentary_evidence_requirements' => $this->stringList('documentary_evidence_requirements'),
            'manual_co_accountable_units' => $this->stringList('manual_co_accountable_units'),
        ];
    }

    /** @return list<int> */
    public function coAccountableDepartmentIds(): array
    {
        $input = $this->input('co_accountable_department_ids', []);

        if (! is_array($input)) {
            return [];
        }

        $departmentIds = [];

        foreach ($input as $id) {
            if (is_numeric($id)) {
                $departmentIds[] = (int) $id;
            }
        }

        return $departmentIds;
    }

    protected function prepareForValidation(): void
    {
        $singleLineFields = ['target_unit', 'target_frequency'];
        $data = [];

        foreach (['objective', 'strategy', 'kpi_target_text', 'resources_needed'] as $field) {
            $value = $this->input($field);
            $data[$field] = is_string($value) ? trim($value) : $value;
        }

        foreach ($singleLineFields as $field) {
            $value = $this->input($field);
            $data[$field] = is_string($value) ? Str::squish($value) : $value;
        }

        foreach (['documentary_evidence_requirements', 'manual_co_accountable_units'] as $field) {
            $value = $this->input($field);

            if (is_array($value)) {
                $data[$field] = collect($value)
                    ->map(fn (mixed $entry): mixed => is_string($entry) ? Str::squish($entry) : $entry)
                    ->reject(fn (mixed $entry): bool => $entry === '' || $entry === null)
                    ->values()
                    ->all();
            }
        }

        $this->merge($data);
    }

    /** @return list<string> */
    private function stringList(string $field): array
    {
        $input = $this->input($field, []);

        if (! is_array($input)) {
            return [];
        }

        $values = [];

        foreach ($input as $value) {
            if (is_string($value)) {
                $values[] = $value;
            }
        }

        return $values;
    }
}
