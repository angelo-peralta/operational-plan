<?php

namespace App\Http\Requests;

class DeleteKeyResultAreaRequest extends PlanningRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('delete', $this->keyResultArea()) ?? false;
    }
}
