<?php

namespace App\Models;

use Database\Factories\KeyResultAreaTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $code
 * @property string $name
 * @property string|null $description
 * @property int $sort_order
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $creator
 * @property-read User|null $updater
 */
#[Fillable([
    'code',
    'name',
    'description',
    'sort_order',
    'is_active',
    'created_by',
    'updated_by',
])]

class KeyResultAreaTemplate extends Model
{
    /** @use HasFactory<KeyResultAreaTemplateFactory> */
    use HasFactory;

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

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
