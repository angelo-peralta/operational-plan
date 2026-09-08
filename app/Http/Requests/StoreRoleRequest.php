<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Role::class, 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'access_level' => ['required', 'string', Rule::enum(UserRole::class)],
            'slug' => ['prohibited'],
            'is_system' => ['prohibited'],
        ];
    }

    /** @return list<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('name')) {
                    return;
                }

                $slug = Str::slug($this->string('name')->toString());

                if ($slug === '') {
                    $validator->errors()->add('name', __('The role name must contain letters or numbers.'));

                    return;
                }

                if (Str::length($slug) > 255) {
                    $validator->errors()->add('name', __('The generated role identifier may not exceed 255 characters.'));

                    return;
                }

                if (Role::query()->where('slug', $slug)->exists()) {
                    $validator->errors()->add('name', __('A role with a similar name already exists.'));
                }
            },
        ];
    }

    /** @return array{name: string, slug: string, description: string|null, access_level: string} */
    public function validatedData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'slug' => Str::slug($this->string('name')->toString()),
            'description' => $this->filled('description')
                ? $this->string('description')->toString()
                : null,
            'access_level' => $this->string('access_level')->toString(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $description = $this->input('description');

        $this->merge([
            'name' => is_string($this->input('name'))
                ? Str::squish($this->input('name'))
                : $this->input('name'),
            'description' => is_string($description) && Str::of($description)->trim()->isNotEmpty()
                ? Str::of($description)->trim()->toString()
                : null,
        ]);
    }
}
