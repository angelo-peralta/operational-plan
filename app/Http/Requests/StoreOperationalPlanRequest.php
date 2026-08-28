<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\OperationalPlan;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOperationalPlanRequest extends OperationalPlanFieldsRequest
{
    private ?Department $resolvedDepartment = null;

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
        $departmentRule = $this->user()?->isSuperAdmin()
            ? ['required', 'integer', Rule::exists(Department::class, 'id')->where('is_active', true)]
            : ['prohibited'];

        return [
            ...parent::rules(),
            'department_id' => $departmentRule,
        ];
    }

    public function department(): Department
    {
        if ($this->resolvedDepartment !== null) {
            return $this->resolvedDepartment;
        }

        $departmentId = $this->planningDepartmentId();

        abort_if($departmentId === null, 422);

        return $this->resolvedDepartment = Department::query()->findOrFail($departmentId);
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                $departmentId = $this->planningDepartmentId();

                if ($departmentId === null || $validator->errors()->has('department_id')) {
                    return;
                }

                if (OperationalPlan::query()
                    ->whereBelongsTo($this->selectedAcademicYear())
                    ->where('department_id', $departmentId)
                    ->exists()) {
                    $validator->errors()->add(
                        'department_id',
                        __('This department already has an Operational Plan for the selected Academic Year.'),
                    );
                }
            },
        ];
    }

    protected function planningDepartmentId(): ?int
    {
        if ($this->user()?->isSuperAdmin()) {
            return $this->filled('department_id') ? $this->integer('department_id') : null;
        }

        return $this->user()?->department_id;
    }
}
