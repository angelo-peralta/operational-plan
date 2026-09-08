<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class SubmitAccomplishmentRequest extends MonitoringRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('submit', $this->accomplishment()) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'plan_item_id' => ['prohibited'],
            'reporting_period_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'department_id' => ['prohibited'],
            'reported_value' => ['prohibited'],
            'accomplishment_text' => ['prohibited'],
            'percentage_accomplished' => ['prohibited'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
            'submitted_by' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'resubmitted_at' => ['prohibited'],
            'revision' => ['prohibited'],
            'snapshot' => ['prohibited'],
        ];
    }
}
