<?php

namespace App\Models;

use App\Enums\OrganizationalUnitType;
use Database\Factories\OrganizationalUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string|null $code
 * @property string|null $acronym
 * @property OrganizationalUnitType $type
 * @property string|null $description
 * @property bool $is_active
 * @property int $sort_order
 * @property-read OrganizationalUnit|null $parent
 * @property-read Collection<int, OrganizationalUnit> $children
 * @property-read Collection<int, OrganizationalUnitUserAssignment> $assignments
 */
#[Fillable(['parent_id', 'name', 'code', 'acronym', 'type', 'description', 'is_active', 'sort_order'])]
class OrganizationalUnit extends Model
{
    /** @use HasFactory<OrganizationalUnitFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => true, 'type' => 'office', 'sort_order' => 0];

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<OrganizationalUnit, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    /** @return HasMany<OrganizationalUnitUserAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(OrganizationalUnitUserAssignment::class);
    }

    /** @return HasMany<OperationalPlan, $this> */
    public function operationalPlans(): HasMany
    {
        return $this->hasMany(OperationalPlan::class);
    }

    /** @return list<int> */
    public function descendantUnitIds(): array
    {
        $children = self::query()->get(['id', 'parent_id'])->groupBy('parent_id');
        $visited = [$this->id => true];
        $pending = [$this->id];
        $descendants = [];

        while ($pending !== []) {
            $parentId = array_pop($pending);
            foreach ($children->get($parentId, collect()) as $child) {
                if (isset($visited[$child->id])) {
                    continue;
                }
                $visited[$child->id] = true;
                $descendants[] = $child->id;
                $pending[] = $child->id;
            }
        }

        return $descendants;
    }

    public function isAncestorOf(OrganizationalUnit $unit): bool
    {
        return in_array($unit->id, $this->descendantUnitIds(), true);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['type' => OrganizationalUnitType::class, 'is_active' => 'boolean', 'sort_order' => 'integer'];
    }
}
