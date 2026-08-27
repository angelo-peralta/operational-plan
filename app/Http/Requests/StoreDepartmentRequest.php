<?php

namespace App\Http\Requests;

use App\Models\Department;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Department::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'code' => ['nullable', 'string', 'max:50', Rule::unique('departments', 'code')],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $description = $this->input('description');

        $data = [
            'name' => is_string($this->input('name')) ? Str::squish($this->input('name')) : $this->input('name'),
            'code' => is_string($code) && Str::of($code)->trim()->isNotEmpty()
                ? Str::upper(Str::squish($code))
                : null,
            'description' => is_string($description) && Str::of($description)->trim()->isNotEmpty()
                ? Str::of($description)->trim()->toString()
                : null,
        ];

        if ($this->has('is_active')) {
            $data['is_active'] = $this->boolean('is_active');
        }

        $this->merge($data);
    }
}
