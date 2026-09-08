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
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $plan_item_id
 * @property int $reporting_period_id
 * @property string|null $reported_value
 * @property string|null $accomplishment_text
 * @property string|null $percentage_accomplished
 * @property AccomplishmentStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property Carbon|null $resubmitted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read PlanItem $planItem
 * @property-read ReportingPeriod $reportingPeriod
 * @property-read User|null $creator
 * @property-read User|null $editor
 * @property-read User|null $submitter
 * @property-read Collection<int, Evidence> $evidence
 * @property-read Collection<int, Evaluation> $evaluations
 * @property-read Collection<int, AccomplishmentSubmission> $submissions
 * @property-read Collection<int, Remark> $remarks
 */
#[Fillable([
    'plan_item_id',
    'reporting_period_id',
    'reported_value',
    'accomplishment_text',
    'percentage_accomplished',
    'status',
    'created_by',
    'updated_by',
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
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

    /** @return HasMany<AccomplishmentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AccomplishmentSubmission::class)
            ->latest('revision');
    }

    /** @return HasManyThrough<Evaluation, AccomplishmentSubmission, $this> */
    public function evaluations(): HasManyThrough
    {
        return $this->hasManyThrough(Evaluation::class, AccomplishmentSubmission::class);
    }

    /** @return MorphMany<Remark, $this> */
    public function remarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable');
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
