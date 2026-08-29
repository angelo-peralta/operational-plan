<?php

namespace App\Models;

use Database\Factories\EvidenceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $accomplishment_id
 * @property int $uploaded_by
 * @property string|null $evidence_type
 * @property string|null $title
 * @property string|null $description
 * @property string $original_filename
 * @property string $stored_path
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $checksum
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Accomplishment $accomplishment
 * @property-read User $uploader
 */
#[Fillable([
    'accomplishment_id',
    'uploaded_by',
    'evidence_type',
    'title',
    'description',
    'original_filename',
    'stored_path',
    'mime_type',
    'file_size',
    'checksum',
])]
class Evidence extends Model
{
    /** @use HasFactory<EvidenceFactory> */
    use HasFactory;

    protected $table = 'evidence';

    /** @return BelongsTo<Accomplishment, $this> */
    public function accomplishment(): BelongsTo
    {
        return $this->belongsTo(Accomplishment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['file_size' => 'integer'];
    }
}
