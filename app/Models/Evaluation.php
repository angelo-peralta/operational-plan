<?php

namespace App\Models;

use App\Enums\EvaluationDecision;
use App\Enums\EvaluationRating;
use Database\Factories\EvaluationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $accomplishment_submission_id
 * @property int $reviewer_id
 * @property int|null $organizational_unit_id
 * @property EvaluationDecision $decision
 * @property EvaluationRating|null $rating
 * @property string|null $remarks
 * @property bool $is_override
 * @property int|null $overrides_evaluation_id
 * @property Carbon $reviewed_at
 * @property Carbon $created_at
 * @property-read AccomplishmentSubmission $submission
 * @property-read User $reviewer
 * @property-read OrganizationalUnit|null $organizationalUnit
 * @property-read Evaluation|null $overriddenEvaluation
 * @property-read Collection<int, Evaluation> $overridingEvaluations
 * @property-read Collection<int, Remark> $fieldRemarks
 */
#[Fillable([
    'accomplishment_submission_id',
    'reviewer_id',
    'organizational_unit_id',
    'decision',
    'rating',
    'remarks',
    'is_override',
    'overrides_evaluation_id',
    'reviewed_at',
    'created_at',
])]
class Evaluation extends Model
{
    /** @use HasFactory<EvaluationFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /** @return BelongsTo<AccomplishmentSubmission, $this> */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(AccomplishmentSubmission::class, 'accomplishment_submission_id');
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return BelongsTo<Evaluation, $this> */
    public function overriddenEvaluation(): BelongsTo
    {
        return $this->belongsTo(self::class, 'overrides_evaluation_id');
    }

    /** @return HasMany<Evaluation, $this> */
    public function overridingEvaluations(): HasMany
    {
        return $this->hasMany(self::class, 'overrides_evaluation_id');
    }

    /** @return MorphMany<Remark, $this> */
    public function fieldRemarks(): MorphMany
    {
        return $this->morphMany(Remark::class, 'remarkable');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'decision' => EvaluationDecision::class,
            'rating' => EvaluationRating::class,
            'is_override' => 'boolean',
            'reviewed_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
