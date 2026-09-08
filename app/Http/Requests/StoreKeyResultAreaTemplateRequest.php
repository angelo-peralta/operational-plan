<?php

namespace App\Http\Requests;

use App\Models\KeyResultAreaTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreKeyResultAreaTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', KeyResultAreaTemplate::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:100', Rule::unique(KeyResultAreaTemplate::class, 'code')],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
        ];
    }

    /** @return array{code: string|null, name: string, description: string|null, sort_order: int, is_active: bool} */
    public function validatedData(): array
    {
        return [
            'code' => $this->filled('code') ? $this->string('code')->toString() : null,
            'name' => $this->string('name')->toString(),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'sort_order' => $this->integer('sort_order'),
            'is_active' => $this->boolean('is_active'),
        ];
    }

    protected function prepareForValidation(): void
    {
        $code = $this->input('code');
        $description = $this->input('description');

        $this->merge([
            'code' => is_string($code) && Str::of($code)->squish()->isNotEmpty()
                ? Str::upper(Str::squish($code))
                : null,
            'name' => is_string($this->input('name'))
                ? Str::squish($this->input('name'))
                : $this->input('name'),
            'description' => is_string($description) && Str::of($description)->trim()->isNotEmpty()
                ? Str::of($description)->trim()->toString()
                : null,
        ]);
    }
}
