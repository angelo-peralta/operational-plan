<?php

namespace App\Http\Requests;

use App\Models\KeyResultAreaTemplate;
use Illuminate\Validation\Rule;

class UpdateKeyResultAreaTemplateRequest extends StoreKeyResultAreaTemplateRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->keyResultAreaTemplate()) ?? false;
    }

    public function rules(): array
    {
        $rules = parent::rules();
        $rules['code'] = [
            'nullable',
            'string',
            'max:100',
            Rule::unique(KeyResultAreaTemplate::class, 'code')->ignore($this->keyResultAreaTemplate()),
        ];

        return $rules;
    }

    private function keyResultAreaTemplate(): KeyResultAreaTemplate
    {
        $template = $this->route('key_result_area_template');

        abort_unless($template instanceof KeyResultAreaTemplate, 404);

        return $template;
    }
}
