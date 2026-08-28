<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ApproveOperationalPlanRequest extends PlanningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('approve', $this->operationalPlan()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
        ];
    }
}
