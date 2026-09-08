<?php

namespace App\Http\Requests;

use App\Models\OrganizationalUnit;

class UpdateOrganizationalUnitRequest extends StoreOrganizationalUnitRequest
{
    public function authorize(): bool
    {
        $unit = $this->route('organizational_unit');

        return $unit instanceof OrganizationalUnit && ($this->user()?->can('update', $unit) ?? false);
    }
}
