<?php

namespace App\Http\Requests;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'role' => ['required', 'string', Rule::enum(UserRole::class)],
            'department_id' => [
                Rule::requiredIf($this->input('role') === UserRole::DepartmentUser->value),
                'nullable',
                'integer',
                Rule::exists(Department::class, 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * Get the validated user attributes.
     *
     * @return array{name: string, email: string, password: string, role: string, department_id: int|null}
     */
    public function validatedData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'role' => $this->string('role')->toString(),
            'department_id' => $this->filled('department_id') ? $this->integer('department_id') : null,
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? Str::lower(Str::of($this->input('email'))->trim()->toString()) : $this->input('email'),
        ]);
    }
}
