<?php

namespace App\Models;

use App\Enums\TargetOperator;
use Database\Factories\PlanItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $key_result_area_id
 * @property string $objective
 * @property string|null $strategy
 * @property string $kpi_target_text
 * @property string|null $target_value
 * @property string|null $target_unit
 * @property TargetOperator|null $target_operator
 * @property string|null $target_frequency
 * @property string|null $resources_needed
 * @property list<string>|null $documentary_evidence_requirements
 * @property list<string>|null $manual_co_accountable_units
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read KeyResultArea $keyResultArea
 * @property-read User $creator
 * @property-read User $updater
 * @property-read Collection<int, Department> $coAccountableDepartments
 */
#[Fillable([
    'key_result_area_id',
    'objective',
    'strategy',
    'kpi_target_text',
    'target_value',
    'target_unit',
    'target_operator',
    'target_frequency',
    'resources_needed',
    'documentary_evidence_requirements',
    'manual_co_accountable_units',
    'sort_order',
    'created_by',
    'updated_by',
])]
class PlanItem extends Model
{
    /** @use HasFactory<PlanItemFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'documentary_evidence_requirements' => '[]',
        'manual_co_accountable_units' => '[]',
    ];

    /** @return BelongsTo<KeyResultArea, $this> */
    public function keyResultArea(): BelongsTo
    {
        return $this->belongsTo(KeyResultArea::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsToMany<Department, $this> */
    public function coAccountableDepartments(): BelongsToMany
    {
        return $this->belongsToMany(
            Department::class,
            'plan_item_co_accountables',
        )->orderBy('name');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'target_value' => 'decimal:4',
            'target_operator' => TargetOperator::class,
            'documentary_evidence_requirements' => 'array',
            'manual_co_accountable_units' => 'array',
            'sort_order' => 'integer',
        ];
    }
}
