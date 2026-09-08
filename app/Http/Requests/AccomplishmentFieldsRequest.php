<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

abstract class AccomplishmentFieldsRequest extends MonitoringRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reported_value' => [
                'nullable',
                'required_without:accomplishment_text',
                'numeric',
                'min:0',
                'max:99999999999',
            ],
            'accomplishment_text' => [
                'nullable',
                'required_without:reported_value',
                'string',
                'max:20000',
            ],
            'plan_item_id' => ['prohibited'],
            'reporting_period_id' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'department_id' => ['prohibited'],
            'percentage_accomplished' => ['prohibited'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'updated_by' => ['prohibited'],
            'submitted_by' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'resubmitted_at' => ['prohibited'],
            'evidence_files' => ['nullable', 'array', 'max:50'],
            'evidence_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /** @return array{reported_value: string|null, accomplishment_text: string|null} */
    public function validatedData(): array
    {
        return [
            'reported_value' => $this->filled('reported_value')
                ? $this->string('reported_value')->toString()
                : null,
            'accomplishment_text' => $this->filled('accomplishment_text')
                ? $this->string('accomplishment_text')->toString()
                : null,
        ];
    }

    /** @return list<UploadedFile> */
    public function evidenceFiles(): array
    {
        return array_values($this->file('evidence_files', []));
    }

    protected function prepareForValidation(): void
    {
        $accomplishmentText = $this->input('accomplishment_text');

        $this->merge([
            'accomplishment_text' => is_string($accomplishmentText)
                ? trim($accomplishmentText)
                : $accomplishmentText,
        ]);
    }
}
