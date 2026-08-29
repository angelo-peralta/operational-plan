<?php

namespace App\Models;

use App\Enums\AccomplishmentStatus;
use Database\Factories\AccomplishmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plan_item_id
 * @property int $reporting_period_id
 * @property string|null $reported_value
 * @property string|null $accomplishment_text
 * @property string|null $percentage_accomplished
 * @property AccomplishmentStatus $status
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $resubmitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PlanItem $planItem
 * @property-read ReportingPeriod $reportingPeriod
 * @property-read User|null $submitter
 * @property-read Collection<int, Evidence> $evidence
 */
#[Fillable([
    'plan_item_id',
    'reporting_period_id',
    'reported_value',
    'accomplishment_text',
    'percentage_accomplished',
    'status',
    'submitted_by',
    'submitted_at',
    'resubmitted_at',
])]
class Accomplishment extends Model
{
    /** @use HasFactory<AccomplishmentFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return BelongsTo<PlanItem, $this> */
    public function planItem(): BelongsTo
    {
        return $this->belongsTo(PlanItem::class);
    }

    /** @return BelongsTo<ReportingPeriod, $this> */
    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return HasMany<Evidence, $this> */
    public function evidence(): HasMany
    {
        return $this->hasMany(Evidence::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'reported_value' => 'decimal:4',
            'percentage_accomplished' => 'decimal:4',
            'status' => AccomplishmentStatus::class,
            'submitted_at' => 'datetime',
            'resubmitted_at' => 'datetime',
        ];
    }
}
