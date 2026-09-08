<?php

namespace App\Models;

use App\Enums\OperationalPlanStatus;
use Database\Factories\OperationalPlanStatusHistoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $operational_plan_id
 * @property int $actor_id
 * @property OperationalPlanStatus|null $from_status
 * @property OperationalPlanStatus $to_status
 * @property string|null $action
 * @property string|null $actor_role
 * @property list<int>|null $actor_organizational_unit_ids
 * @property string|null $remarks
 * @property array<string, mixed>|null $snapshot
 * @property Carbon $created_at
 * @property-read OperationalPlan $operationalPlan
 * @property-read User $actor
 */
#[Fillable([
    'operational_plan_id',
    'actor_id',
    'from_status',
    'to_status',
    'action',
    'actor_role',
    'actor_organizational_unit_ids',
    'remarks',
    'snapshot',
    'created_at',
])]
class OperationalPlanStatusHistory extends Model
{
    /** @use HasFactory<OperationalPlanStatusHistoryFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<OperationalPlan, $this> */
    public function operationalPlan(): BelongsTo
    {
        return $this->belongsTo(OperationalPlan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'from_status' => OperationalPlanStatus::class,
            'to_status' => OperationalPlanStatus::class,
            'snapshot' => 'array',
            'actor_organizational_unit_ids' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
