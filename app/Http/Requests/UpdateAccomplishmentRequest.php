<?php

namespace App\Http\Requests;

class UpdateAccomplishmentRequest extends AccomplishmentFieldsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->accomplishment()) ?? false;
    }
}
