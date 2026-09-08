<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\OrganizationalUnit;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $user = $this->route('user');
        $role = is_string($this->input('role')) ? UserRole::tryFrom($this->input('role')) : null;

        return [
            ...$this->profileRules($user instanceof User ? $user->id : null),
            'password' => $user instanceof User ? ['nullable', 'string', Password::default(), 'confirmed'] : $this->passwordRules(),
            'role' => ['required', Rule::enum(UserRole::class)],
            'role_id' => ['prohibited'],
            'department_id' => ['prohibited'],
            'legacy_role' => ['prohibited'],
            'legacy_role_id' => ['prohibited'],
            'assignments' => ['present', 'array', 'max:50', Rule::when($role === UserRole::SystemAdmin, ['max:0'])],
            'assignments.*' => ['array:organizational_unit_id,assignment_type,position_title,is_primary,starts_on,ends_on,is_active'],
            'assignments.*.organizational_unit_id' => ['required', 'integer', 'distinct', Rule::exists(OrganizationalUnit::class, 'id')],
            'assignments.*.assignment_type' => ['required', Rule::in([$role?->assignmentType()?->value])],
            'assignments.*.position_title' => ['nullable', 'string', 'max:255'],
            'assignments.*.is_primary' => ['required', 'boolean'],
            'assignments.*.is_active' => ['required', 'boolean'],
            'assignments.*.starts_on' => ['nullable', 'date_format:Y-m-d'],
            'assignments.*.ends_on' => ['nullable', 'date_format:Y-m-d'],
        ];
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $primaryCount = 0;
            foreach ($validator->validated()['assignments'] as $index => $assignment) {
                if (filled($assignment['starts_on'] ?? null) && filled($assignment['ends_on'] ?? null)
                    && $assignment['ends_on'] < $assignment['starts_on']) {
                    $validator->errors()->add("assignments.{$index}.ends_on", __('The end date must be on or after the start date.'));
                }
                if ($assignment['is_active'] && $assignment['is_primary']) {
                    $primaryCount++;
                }
            }
            if ($primaryCount > 1) {
                $validator->errors()->add('assignments', __('Only one active assignment may be primary.'));
            }
        }];
    }

    /** @return array<string, mixed> */
    public function validatedData(): array
    {
        return $this->safe()->except(['password_confirmation']);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? Str::lower(trim($this->input('email'))) : $this->input('email'),
        ]);
    }
}
