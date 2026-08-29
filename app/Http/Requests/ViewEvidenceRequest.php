<?php

namespace App\Http\Requests;

class ViewEvidenceRequest extends MonitoringRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('view', $this->evidence()) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
