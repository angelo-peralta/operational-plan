<?php

namespace App\Models;

use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, PlanItem> $coAccountablePlanItems
 * @property-read Collection<int, OperationalPlan> $operationalPlans
 * @property-read Collection<int, User> $users
 */
#[Fillable(['name', 'code', 'description', 'is_active'])]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the users assigned to the department.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function operationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class);
    }

    /** @return BelongsToMany<PlanItem, $this> */
    public function coAccountablePlanItems(): BelongsToMany
    {
        return $this->belongsToMany(
            PlanItem::class,
            'plan_item_co_accountables',
        );
    }

    /**
     * Determine whether the department is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
