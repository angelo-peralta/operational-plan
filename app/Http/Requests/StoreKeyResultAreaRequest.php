<?php

namespace App\Http\Requests;

use App\Models\KeyResultArea;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class StoreKeyResultAreaRequest extends PlanningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [
            KeyResultArea::class,
            $this->operationalPlan(),
        ]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'operational_plan_id' => ['prohibited'],
            'sort_order' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
        ];
    }

    /** @return array{code: string|null, name: string, description: string|null} */
    public function validatedData(): array
    {
        return [
            'code' => $this->filled('code') ? $this->string('code')->toString() : null,
            'name' => $this->string('name')->toString(),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->normalizeSingleLine('code'),
            'name' => $this->normalizeSingleLine('name'),
            'description' => is_string($this->input('description')) ? trim($this->input('description')) : $this->input('description'),
        ]);
    }

    private function normalizeSingleLine(string $field): mixed
    {
        $value = $this->input($field);

        if (! is_string($value)) {
            return $value;
        }

        $normalized = Str::squish($value);

        return $normalized === '' ? null : $normalized;
    }
}
