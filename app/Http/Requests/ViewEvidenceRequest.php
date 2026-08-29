<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;

class ViewEvidenceRequest extends MonitoringRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->evidence()) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [];
    }
}
