<?php

namespace App\Http\Requests;

class UpdatePlanItemRequest extends PlanItemFieldsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->planItem()) ?? false;
    }
}
