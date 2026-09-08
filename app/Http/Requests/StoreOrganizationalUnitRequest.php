<?php

namespace App\Http\Requests;

use App\Enums\OrganizationalUnitType;
use App\Models\OrganizationalUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreOrganizationalUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', OrganizationalUnit::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $unit = $this->route('organizational_unit');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(OrganizationalUnit::class, 'name')->ignore($unit instanceof OrganizationalUnit ? $unit : null)],
            'code' => ['nullable', 'string', 'max:255', Rule::unique(OrganizationalUnit::class, 'code')->ignore($unit instanceof OrganizationalUnit ? $unit : null)],
            'parent_id' => ['nullable', 'integer', Rule::exists(OrganizationalUnit::class, 'id')],
            'acronym' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::enum(OrganizationalUnitType::class)],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:1000000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
            'code' => is_string($this->input('code')) ? Str::upper(trim($this->input('code'))) : $this->input('code'),
        ]);
    }
}
