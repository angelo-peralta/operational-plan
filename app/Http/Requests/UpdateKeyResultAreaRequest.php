<?php

namespace App\Http\Requests;

class UpdateKeyResultAreaRequest extends StoreKeyResultAreaRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->keyResultArea()) ?? false;
    }
}
