<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property UserRole $access_level
 * @property bool $is_system
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read int|null $users_count
 * @property-read Collection<int, User> $users
 */
#[Fillable(['name', 'slug', 'description', 'access_level', 'is_system', 'is_active'])]
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /** @var array<string, mixed> */
    protected $attributes = [
        'access_level' => 'staff',
        'is_system' => false,
        'is_active' => true,
    ];

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function requiresDepartment(): bool
    {
        return $this->access_level === UserRole::Staff;
    }

    public static function systemFor(UserRole $accessLevel): self
    {
        return self::query()
            ->where('slug', $accessLevel->value)
            ->where('is_system', true)
            ->where('is_active', true)
            ->firstOrFail();
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'access_level' => UserRole::class,
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }
}
