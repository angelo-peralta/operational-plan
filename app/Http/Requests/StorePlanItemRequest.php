<?php

namespace App\Http\Requests;

use App\Models\PlanItem;

class StorePlanItemRequest extends PlanItemFieldsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [
            PlanItem::class,
            $this->keyResultArea(),
        ]) ?? false;
    }
}
