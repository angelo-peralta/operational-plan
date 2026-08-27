<?php

namespace App\Http\Requests;

use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            && ($this->user()?->can('update', $user) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;

        return [
            ...$this->profileRules($userId),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
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
     * @return array{name: string, email: string, password?: string, role: string, department_id: int|null}
     */
    public function validatedData(): array
    {
        $data = [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'role' => $this->string('role')->toString(),
            'department_id' => $this->filled('department_id') ? $this->integer('department_id') : null,
        ];

        if ($this->filled('password')) {
            $data['password'] = $this->string('password')->toString();
        }

        return $data;
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
