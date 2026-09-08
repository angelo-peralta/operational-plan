<?php

namespace App\Models;

use App\Enums\RemarkStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['remark_id', 'actor_id', 'from_status', 'to_status', 'note', 'created_at'])]

class RemarkStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /** @return BelongsTo<Remark, $this> */
    public function remark(): BelongsTo
    {
        return $this->belongsTo(Remark::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['from_status' => RemarkStatus::class, 'to_status' => RemarkStatus::class, 'created_at' => 'datetime'];
    }
}
