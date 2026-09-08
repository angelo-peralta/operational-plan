<?php

namespace App\Http\Requests;

use App\Enums\RemarkStatus;
use App\Models\Remark;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateRemarkStatusRequest extends PlanningRequest
{
    private ?Remark $resolvedRemark = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $remark = $this->remark();

        return match ($this->input('status')) {
            RemarkStatus::Addressed->value => $this->user()?->can('address', $remark) ?? false,
            RemarkStatus::Resolved->value => $this->user()?->can('resolve', $remark) ?? false,
            default => false,
        };
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(RemarkStatus::class), Rule::notIn([RemarkStatus::Open->value])],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function remark(): Remark
    {
        if ($this->resolvedRemark instanceof Remark) {
            return $this->resolvedRemark;
        }

        $routeRemark = $this->route('remark');
        $remarkId = $routeRemark instanceof Remark
            ? $routeRemark->id
            : (is_string($routeRemark) && ctype_digit($routeRemark) ? (int) $routeRemark : 0);

        abort_unless($remarkId > 0, 404);

        return $this->resolvedRemark = Remark::query()
            ->whereKey($remarkId)
            ->whereBelongsTo($this->operationalPlan())
            ->firstOrFail();
    }
}
