<?php

namespace App\Models;

use Database\Factories\KeyResultAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $operational_plan_id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property int $created_by
 * @property int $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read OperationalPlan $operationalPlan
 * @property-read User $creator
 * @property-read User $updater
 * @property-read Collection<int, PlanItem> $planItems
 */
#[Fillable([
    'operational_plan_id',
    'code',
    'name',
    'description',
    'sort_order',
    'created_by',
    'updated_by',
])]
class KeyResultArea extends Model
{
    /** @use HasFactory<KeyResultAreaFactory> */
    use HasFactory;

    /** @return BelongsTo<OperationalPlan, $this> */
    public function operationalPlan(): BelongsTo
    {
        return $this->belongsTo(OperationalPlan::class);
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

    /** @return HasMany<PlanItem, $this> */
    public function planItems(): HasMany
    {
        return $this->hasMany(PlanItem::class)->orderBy('sort_order');
    }
}
