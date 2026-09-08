<?php

namespace App\Http\Requests;

use App\Models\OperationalPlan;
use App\Models\OrganizationalUnit;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOperationalPlanRequest extends OperationalPlanFieldsRequest
{
    private ?OrganizationalUnit $resolvedOrganizationalUnit = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [
            OperationalPlan::class,
            $this->selectedAcademicYear(),
        ]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'organizational_unit_id' => ['required', 'integer', Rule::exists(OrganizationalUnit::class, 'id')->where('is_active', true), Rule::in($this->organizationalScope()->manageableUnitIds($this->user()))],
        ];
    }

    public function organizationalUnit(): OrganizationalUnit
    {
        if ($this->resolvedOrganizationalUnit !== null) {
            return $this->resolvedOrganizationalUnit;
        }

        $organizationalUnitId = $this->planningOrganizationalUnitId();

        abort_if($organizationalUnitId === null, 422);

        return $this->resolvedOrganizationalUnit = OrganizationalUnit::query()->findOrFail($organizationalUnitId);
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $organizationalUnitId = $this->planningOrganizationalUnitId();

                if ($organizationalUnitId === null || $validator->errors()->has('organizational_unit_id')) {
                    return;
                }

                if (OperationalPlan::query()
                    ->whereBelongsTo($this->selectedAcademicYear())
                    ->where('organizational_unit_id', $organizationalUnitId)
                    ->exists()) {
                    $validator->errors()->add(
                        'organizational_unit_id',
                        __('This organizational unit already has an Operational Plan for the selected Academic Year.'),
                    );
                }
            },
        ];
    }

    protected function planningOrganizationalUnitId(): ?int
    {
        return $this->filled('organizational_unit_id') ? $this->integer('organizational_unit_id') : null;
    }
}
