<?php

namespace App\Models;

use App\Enums\OperationalPlanStatus;
use Database\Factories\OperationalPlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $academic_year_id
 * @property int $department_id
 * @property int|null $accountable_user_id
 * @property string|null $accountable_name
 * @property string|null $accountable_position
 * @property string $goal
 * @property OperationalPlanStatus $status
 * @property int $created_by
 * @property int $updated_by
 * @property int|null $submitted_by
 * @property Carbon|null $submitted_at
 * @property int|null $approved_by
 * @property Carbon|null $approved_at
 * @property int|null $returned_by
 * @property Carbon|null $returned_at
 * @property int|null $closed_by
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AcademicYear $academicYear
 * @property-read Department $department
 * @property-read User|null $accountableUser
 * @property-read User $creator
 * @property-read User $updater
 * @property-read User|null $submitter
 * @property-read User|null $approver
 * @property-read User|null $returner
 * @property-read User|null $closer
 * @property-read Collection<int, KeyResultArea> $keyResultAreas
 * @property-read Collection<int, PlanItem> $planItems
 * @property-read Collection<int, OperationalPlanStatusHistory> $statusHistories
 */
#[Fillable([
    'academic_year_id',
    'department_id',
    'accountable_user_id',
    'accountable_name',
    'accountable_position',
    'goal',
    'status',
    'created_by',
    'updated_by',
    'submitted_by',
    'submitted_at',
    'approved_by',
    'approved_at',
    'returned_by',
    'returned_at',
    'closed_by',
    'closed_at',
])]
class OperationalPlan extends Model
{
    /** @use HasFactory<OperationalPlanFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'draft',
    ];

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<User, $this> */
    public function accountableUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accountable_user_id');
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

    /** @return BelongsTo<User, $this> */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return BelongsTo<User, $this> */
    public function returner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    /** @return BelongsTo<User, $this> */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /** @return HasMany<KeyResultArea, $this> */
    public function keyResultAreas(): HasMany
    {
        return $this->hasMany(KeyResultArea::class)->orderBy('sort_order');
    }

    /** @return HasManyThrough<PlanItem, KeyResultArea, $this> */
    public function planItems(): HasManyThrough
    {
        return $this->hasManyThrough(PlanItem::class, KeyResultArea::class);
    }

    /** @return HasMany<OperationalPlanStatusHistory, $this> */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(OperationalPlanStatusHistory::class)
            ->latest('created_at')
            ->latest('id');
    }

    public function isEditable(): bool
    {
        return $this->academicYear->isOpen() && $this->status->isEditable();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => OperationalPlanStatus::class,
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'returned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }
}
