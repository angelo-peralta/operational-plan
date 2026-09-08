<?php

namespace App\Models;

use App\Enums\RemarkCategory;
use App\Enums\RemarkField;
use App\Enums\RemarkStatus;
use Database\Factories\RemarkFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $academic_year_id
 * @property int $operational_plan_id
 * @property int $author_id
 * @property string $remarkable_type
 * @property int $remarkable_id
 * @property RemarkField|null $field_key
 * @property int|null $reporting_period_id
 * @property RemarkCategory $category
 * @property string $content
 * @property RemarkStatus $status
 * @property int|null $addressed_by
 * @property Carbon|null $addressed_at
 * @property int|null $resolved_by
 * @property Carbon|null $resolved_at
 * @property Carbon|null $created_at
 * @property-read AcademicYear $academicYear
 * @property-read OperationalPlan $operationalPlan
 * @property-read User $author
 * @property-read OperationalPlan|KeyResultArea|PlanItem|Accomplishment|Evidence|Evaluation $remarkable
 * @property-read ReportingPeriod|null $reportingPeriod
 */
#[Fillable([
    'academic_year_id', 'operational_plan_id', 'author_id', 'remarkable_type', 'remarkable_id',
    'field_key', 'reporting_period_id', 'category', 'content', 'status', 'addressed_by',
    'addressed_at', 'resolved_by', 'resolved_at',
])]
class Remark extends Model
{
    /** @use HasFactory<RemarkFactory> */
    use HasFactory;

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<OperationalPlan, $this> */
    public function operationalPlan(): BelongsTo
    {
        return $this->belongsTo(OperationalPlan::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return MorphTo<Model, $this> */
    public function remarkable(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<ReportingPeriod, $this> */
    public function reportingPeriod(): BelongsTo
    {
        return $this->belongsTo(ReportingPeriod::class);
    }

    /** @return BelongsTo<User, $this> */
    public function addressedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'addressed_by');
    }

    /** @return BelongsTo<User, $this> */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /** @return HasMany<RemarkStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(RemarkStatusHistory::class)->oldest('created_at')->oldest('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'field_key' => RemarkField::class,
            'category' => RemarkCategory::class,
            'status' => RemarkStatus::class,
            'addressed_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }
}
