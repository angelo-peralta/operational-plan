<?php

namespace App\Actions\Accomplishments;

use App\Enums\AccomplishmentStatus;
use App\Enums\EvaluationDecision;
use App\Enums\OrganizationalUnitType;
use App\Models\Accomplishment;
use App\Models\AccomplishmentSubmission;
use App\Models\Evaluation;

class ResolveEffectiveEvaluation
{
    public function handle(Accomplishment $accomplishment): ?Evaluation
    {
        if ($accomplishment->status !== AccomplishmentStatus::Accepted) {
            return null;
        }

        $submission = $accomplishment->submissions->first();

        if (! $submission instanceof AccomplishmentSubmission) {
            return null;
        }

        $evaluations = $submission->relationLoaded('evaluations')
            ? $submission->evaluations
            : $submission->evaluations()->with('organizationalUnit')->get();
        $ratedEvaluations = $evaluations
            ->filter(fn (Evaluation $evaluation): bool => $evaluation->decision === EvaluationDecision::Accepted && $evaluation->rating !== null)
            ->sortByDesc(fn (Evaluation $evaluation): string => implode('|', [
                $evaluation->reviewed_at->format('Y-m-d H:i:s.u'),
                str_pad((string) $evaluation->id, 20, '0', STR_PAD_LEFT),
            ]));

        return $ratedEvaluations->first(
            fn (Evaluation $evaluation): bool => $evaluation->organizationalUnit?->type === OrganizationalUnitType::University,
        ) ?? $ratedEvaluations->first();
    }
}
