<?php

namespace App\Actions\Evidence;

use App\Actions\Accomplishments\LockAccomplishmentForMutation;
use App\Models\Accomplishment;
use App\Models\Evidence;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class UploadEvidence
{
    public function __construct(private LockAccomplishmentForMutation $lockAccomplishment) {}

    /** @param array{evidence_type: string|null, title: string|null, description: string|null} $data */
    public function handle(
        Accomplishment $accomplishment,
        User $actor,
        UploadedFile $file,
        array $data,
    ): Evidence {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($accomplishment, $actor, $file, $data, &$storedPath): Evidence {
                $context = $this->lockAccomplishment->handle(
                    $accomplishment->plan_item_id,
                    $accomplishment->reporting_period_id,
                    $accomplishment->id,
                );
                $lockedAccomplishment = $context['accomplishment'];
                assert($lockedAccomplishment instanceof Accomplishment);

                Gate::forUser($actor)->authorize('create', [Evidence::class, $lockedAccomplishment]);

                $extension = $file->guessExtension() ?: $file->extension();
                $storedPath = sprintf(
                    'evidence/%d/%d/%s.%s',
                    $context['operationalPlan']->academic_year_id,
                    $lockedAccomplishment->id,
                    Str::uuid(),
                    $extension,
                );

                $stored = Storage::disk('local')->put($storedPath, $file->getContent());
                throw_unless($stored, new RuntimeException('The evidence file could not be stored.'));

                return Evidence::query()->create([
                    'accomplishment_id' => $lockedAccomplishment->id,
                    'uploaded_by' => $actor->id,
                    'evidence_type' => $data['evidence_type'],
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_path' => $storedPath,
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'file_size' => $file->getSize(),
                    'checksum' => hash_file('sha256', $file->getPathname()) ?: null,
                ]);
            });
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }
}
