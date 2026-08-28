<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class ReopenOperationalPlanRequest extends PlanningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('reopen', $this->operationalPlan()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'remarks' => ['required', 'string', 'max:5000'],
        ];
    }

    public function remarks(): string
    {
        return $this->string('remarks')->toString();
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('remarks'))) {
            $this->merge(['remarks' => Str::of($this->input('remarks'))->trim()->toString()]);
        }
    }
}
