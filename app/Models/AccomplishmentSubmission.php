<?php

namespace App\Models;

use App\Enums\AccomplishmentSubmissionStatus;
use Database\Factories\AccomplishmentSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $accomplishment_id
 * @property int $revision
 * @property int $submitted_by
 * @property AccomplishmentSubmissionStatus $from_status
 * @property AccomplishmentSubmissionStatus $to_status
 * @property int $evidence_count
 * @property array<string, mixed> $snapshot
 * @property Carbon $submitted_at
 * @property-read Accomplishment $accomplishment
 * @property-read Collection<int, Evaluation> $evaluations
 * @property-read User $submitter
 */
#[Fillable([
    'accomplishment_id',
    'revision',
    'submitted_by',
    'from_status',
    'to_status',
    'evidence_count',
    'snapshot',
    'submitted_at',
])]
class AccomplishmentSubmission extends Model
{
    /** @use HasFactory<AccomplishmentSubmissionFactory> */
    use HasFactory;

    public $timestamps = false;

    /** @return BelongsTo<Accomplishment, $this> */
    public function accomplishment(): BelongsTo
    {
        return $this->belongsTo(Accomplishment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return HasMany<Evaluation, $this> */
    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class)
            ->latest('reviewed_at')
            ->latest('id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'revision' => 'integer',
            'from_status' => AccomplishmentSubmissionStatus::class,
            'to_status' => AccomplishmentSubmissionStatus::class,
            'evidence_count' => 'integer',
            'snapshot' => 'array',
            'submitted_at' => 'datetime',
        ];
    }
}
