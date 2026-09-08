<?php

namespace App\Http\Requests;

use App\Models\User;

class UpdateUserRequest extends StoreUserRequest
{
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User && ($this->user()?->can('update', $user) ?? false);
    }
}
