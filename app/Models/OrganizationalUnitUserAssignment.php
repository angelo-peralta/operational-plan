<?php

namespace App\Models;

use App\Enums\AssignmentType;
use Database\Factories\OrganizationalUnitUserAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $organizational_unit_id
 * @property AssignmentType $assignment_type
 * @property string|null $position_title
 * @property bool $is_primary
 * @property bool $is_active
 * @property Carbon|null $starts_on
 * @property Carbon|null $ends_on
 * @property-read OrganizationalUnit $organizationalUnit
 * @property-read User $user
 */
#[Fillable(['user_id', 'organizational_unit_id', 'assignment_type', 'position_title', 'is_primary', 'is_active', 'starts_on', 'ends_on'])]
class OrganizationalUnitUserAssignment extends Model
{
    /** @use HasFactory<OrganizationalUnitUserAssignmentFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = ['is_primary' => false, 'is_active' => true];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<OrganizationalUnit, $this> */
    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'assignment_type' => AssignmentType::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }
}
