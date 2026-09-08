<?php

namespace App\Actions\Accomplishments;

use App\Enums\AccomplishmentStatus;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SubmitAccomplishment
{
    public function __construct(
        private LockAccomplishmentForMutation $lockAccomplishment,
        private RecordAccomplishmentSubmission $recordSubmission,
    ) {}

    public function handle(Accomplishment $accomplishment, User $actor): Accomplishment
    {
        return DB::transaction(function () use ($accomplishment, $actor): Accomplishment {
            $context = $this->lockAccomplishment->handle(
                $accomplishment->plan_item_id,
                $accomplishment->reporting_period_id,
                $accomplishment->id,
            );
            $lockedAccomplishment = $context['accomplishment'];
            assert($lockedAccomplishment instanceof Accomplishment);

            Gate::forUser($actor)->authorize('submit', $lockedAccomplishment);

            if (blank($lockedAccomplishment->reported_value) && blank($lockedAccomplishment->accomplishment_text)) {
                throw ValidationException::withMessages([
                    'accomplishment' => __('Enter a reported value or accomplishment description before submitting.'),
                ]);
            }

            $evidenceCount = $lockedAccomplishment->evidence()->count();

            if ($evidenceCount === 0) {
                throw ValidationException::withMessages([
                    'evidence' => __('Upload at least one evidence file before submitting.'),
                ]);
            }

            $latestSubmission = $lockedAccomplishment->submissions()
                ->latest('revision')
                ->first();

            if ($lockedAccomplishment->status === AccomplishmentStatus::Returned) {
                if (! $latestSubmission instanceof AccomplishmentSubmission) {
                    throw ValidationException::withMessages([
                        'accomplishment' => __('Previous submission history is unavailable. Contact an administrator before resubmitting.'),
                    ]);
                }

                if ($evidenceCount <= $latestSubmission->evidence_count) {
                    throw ValidationException::withMessages([
                        'evidence' => __('Upload at least one additional evidence file before resubmitting.'),
                    ]);
                }
            }

            $fromStatus = $lockedAccomplishment->status;
            $submittedAt = now();
            $isResubmission = $fromStatus === AccomplishmentStatus::Returned;
            $revision = $latestSubmission instanceof AccomplishmentSubmission
                ? $latestSubmission->revision + 1
                : 1;

            $lockedAccomplishment->update([
                'status' => AccomplishmentStatus::UnderReview,
                'updated_by' => $actor->id,
                'submitted_by' => $actor->id,
                'submitted_at' => $lockedAccomplishment->submitted_at ?? $submittedAt,
                'resubmitted_at' => $isResubmission ? $submittedAt : null,
            ]);

            $this->recordSubmission->handle(
                $lockedAccomplishment,
                $actor,
                $fromStatus,
                AccomplishmentStatus::UnderReview,
                $revision,
                $submittedAt,
            );

            return $lockedAccomplishment->refresh();
        });
    }
}
