<?php

namespace App\Models;

use App\Enums\ReportingPeriodStatus;
use Database\Factories\ReportingPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $academic_year_id
 * @property string $name
 * @property string $code
 * @property int $sequence
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property ReportingPeriodStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AcademicYear $academicYear
 */
#[Fillable(['academic_year_id', 'name', 'code', 'sequence', 'starts_on', 'ends_on', 'status'])]
class ReportingPeriod extends Model
{
    /** @use HasFactory<ReportingPeriodFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * Get the academic year that owns the reporting period.
     *
     * @return BelongsTo<AcademicYear, $this>
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Determine whether the reporting period is open.
     */
    public function isOpen(): bool
    {
        return $this->status === ReportingPeriodStatus::Open;
    }

    /**
     * Determine whether submissions are accepted for this reporting period.
     */
    public function acceptsSubmissions(): bool
    {
        return $this->isOpen() && $this->academicYear->isEditableForDepartment();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => ReportingPeriodStatus::class,
        ];
    }
}
