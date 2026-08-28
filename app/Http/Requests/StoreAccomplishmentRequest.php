<?php

namespace App\Http\Requests;

use App\Models\Accomplishment;

class StoreAccomplishmentRequest extends AccomplishmentFieldsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [
            Accomplishment::class,
            $this->planItem(),
            $this->reportingPeriod(),
        ]) ?? false;
    }
}
