<?php

namespace App\Http\Requests;

use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use App\Models\Evaluation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreEvaluationRequest extends MonitoringRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', [Evaluation::class, $this->accomplishment()]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::enum(EvaluationDecision::class)],
            'rating' => [
                Rule::requiredIf(fn (): bool => $this->input('decision') === EvaluationDecision::Accepted->value),
                Rule::prohibitedIf(fn (): bool => in_array($this->input('decision'), [
                    EvaluationDecision::RequestRevision->value,
                    EvaluationDecision::RequestAdditionalEvidence->value,
                ], true)),
                'nullable',
                'integer',
                'between:0,3',
            ],
            'is_override' => ['sometimes', 'boolean'],
            'remarks' => [
                Rule::requiredIf(fn (): bool => $this->remarksAreRequired()),
                'nullable',
                'string',
                'max:5000',
            ],
            'accomplishment_id' => ['prohibited'],
            'accomplishment_submission_id' => ['prohibited'],
            'submission_id' => ['prohibited'],
            'revision' => ['prohibited'],
            'reviewer_id' => ['prohibited'],
            'organizational_unit_id' => ['prohibited'],
            'overrides_evaluation_id' => ['prohibited'],
            'reviewed_at' => ['prohibited'],
            'created_at' => ['prohibited'],
            'updated_at' => ['prohibited'],
            'status' => ['prohibited'],
            'academic_year_id' => ['prohibited'],
            'reporting_period_id' => ['prohibited'],
            'department_id' => ['prohibited'],
            'plan_item_id' => ['prohibited'],
            'snapshot' => ['prohibited'],
        ];
    }

    public function decision(): EvaluationDecision
    {
        return EvaluationDecision::from((string) $this->validated('decision'));
    }

    public function rating(): ?EvaluationRating
    {
        $rating = $this->validated('rating');

        return $rating === null || $rating === ''
            ? null
            : EvaluationRating::from((int) $rating);
    }

    public function remarks(): ?string
    {
        $remarks = $this->validated('remarks');

        return is_string($remarks) && $remarks !== '' ? $remarks : null;
    }

    public function isOverride(): bool
    {
        return $this->boolean('is_override');
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('remarks'))) {
            $this->merge(['remarks' => Str::of($this->input('remarks'))->trim()->toString()]);
        }
    }

    private function remarksAreRequired(): bool
    {
        $decision = $this->input('decision');

        if (in_array($decision, [
            EvaluationDecision::RequestRevision->value,
            EvaluationDecision::RequestAdditionalEvidence->value,
            EvaluationDecision::Rejected->value,
        ], true)) {
            return true;
        }

        return $decision === EvaluationDecision::Accepted->value
            && in_array($this->input('rating'), [0, 1, '0', '1'], true);
    }
}
