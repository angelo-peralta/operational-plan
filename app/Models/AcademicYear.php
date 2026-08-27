<?php

namespace App\Models;

use App\Enums\AcademicYearStatus;
use Database\Factories\AcademicYearFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property int $start_year
 * @property int $end_year
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property AcademicYearStatus $status
 * @property bool $is_current
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, OperationalPlan> $operationalPlans
 * @property-read Collection<int, ReportingPeriod> $reportingPeriods
 */
#[Fillable(['name', 'start_year', 'end_year', 'starts_on', 'ends_on', 'status', 'is_current'])]
class AcademicYear extends Model
{
    /** @use HasFactory<AcademicYearFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'is_current' => false,
    ];

    /**
     * Get the reporting periods in the academic year.
     *
     * @return HasMany<ReportingPeriod, $this>
     */
    public function reportingPeriods(): HasMany
    {
        return $this->hasMany(ReportingPeriod::class)->orderBy('sequence');
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function operationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class);
    }

    /**
     * Determine whether the academic year is current.
     */
    public function isCurrent(): bool
    {
        return $this->is_current;
    }

    /**
     * Determine whether the academic year is open.
     */
    public function isOpen(): bool
    {
        return $this->status === AcademicYearStatus::Open;
    }

    /**
     * Determine whether the academic year is closed.
     */
    public function isClosed(): bool
    {
        return $this->status === AcademicYearStatus::Closed;
    }

    /**
     * Determine whether department users may edit records in this year.
     */
    public function isEditableForDepartment(): bool
    {
        return $this->isOpen();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
            'starts_on' => 'date',
            'ends_on' => 'date',
            'status' => AcademicYearStatus::class,
            'is_current' => 'boolean',
        ];
    }
}
