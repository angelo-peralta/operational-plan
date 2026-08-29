<?php

namespace App\Http\Requests;

use App\Models\Evidence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class StoreEvidenceRequest extends MonitoringRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Evidence::class, $this->accomplishment()]) ?? false;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'evidence_type' => ['nullable', 'string', 'max:100'],
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'accomplishment_id' => ['prohibited'],
            'uploaded_by' => ['prohibited'],
            'stored_path' => ['prohibited'],
            'mime_type' => ['prohibited'],
            'file_size' => ['prohibited'],
            'checksum' => ['prohibited'],
        ];
    }

    public function evidenceFile(): UploadedFile
    {
        $file = $this->file('file');
        assert($file instanceof UploadedFile);

        return $file;
    }

    /** @return array{evidence_type: string|null, title: string|null, description: string|null} */
    public function validatedMetadata(): array
    {
        return [
            'evidence_type' => $this->filled('evidence_type') ? $this->string('evidence_type')->trim()->toString() : null,
            'title' => $this->filled('title') ? $this->string('title')->trim()->toString() : null,
            'description' => $this->filled('description') ? $this->string('description')->trim()->toString() : null,
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['evidence_type', 'title', 'description'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value)]);
            }
        }
    }
}
