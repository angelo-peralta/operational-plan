<?php

namespace App\Http\Requests;

use App\Models\PlanItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ReorderPlanItemsRequest extends PlanningRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('reorder', [
            PlanItem::class,
            $this->keyResultArea(),
        ]) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists(PlanItem::class, 'id'),
            ],
        ];
    }

    /** @return list<int> */
    public function orderedIds(): array
    {
        $input = $this->input('ordered_ids', []);

        if (! is_array($input)) {
            return [];
        }

        $orderedIds = [];

        foreach ($input as $id) {
            if (is_numeric($id)) {
                $orderedIds[] = (int) $id;
            }
        }

        return $orderedIds;
    }

    /** @return array<callable> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('ordered_ids')) {
                    return;
                }

                $expectedIds = $this->keyResultArea()
                    ->planItems()
                    ->pluck('id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();
                $submittedIds = $this->orderedIds();
                sort($expectedIds);
                sort($submittedIds);

                if ($submittedIds !== $expectedIds) {
                    $validator->errors()->add(
                        'ordered_ids',
                        __('The Plan Item order must contain every item in this KRA exactly once.'),
                    );
                }
            },
        ];
    }
}
