<?php

namespace App\Data;

use App\Enums\EvaluationDecision;
use App\Enums\OrganizationalUnitType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class EffectiveEvaluationQuery
{
    /**
     * Return one effective accepted evaluation ID for each accomplishment's
     * latest immutable submission revision.
     */
    public function current(): Builder
    {
        $latestSubmissionRevisions = DB::table('accomplishment_submissions')
            ->select('accomplishment_id')
            ->selectRaw('MAX(revision) AS revision')
            ->groupBy('accomplishment_id');

        $acceptedEvaluations = DB::table('evaluations as accepted_evaluations')
            ->select('accepted_evaluations.accomplishment_submission_id')
            ->selectRaw('MAX(accepted_evaluations.id) AS evaluation_id')
            ->where('accepted_evaluations.decision', EvaluationDecision::Accepted->value)
            ->whereNotNull('accepted_evaluations.rating')
            ->groupBy('accepted_evaluations.accomplishment_submission_id');

        $presidentialEvaluations = DB::table('evaluations as presidential_evaluations')
            ->join(
                'organizational_units as presidential_authorities',
                'presidential_authorities.id',
                '=',
                'presidential_evaluations.organizational_unit_id',
            )
            ->select('presidential_evaluations.accomplishment_submission_id')
            ->selectRaw('MAX(presidential_evaluations.id) AS evaluation_id')
            ->where('presidential_evaluations.decision', EvaluationDecision::Accepted->value)
            ->whereNotNull('presidential_evaluations.rating')
            ->where('presidential_authorities.type', OrganizationalUnitType::University->value)
            ->groupBy('presidential_evaluations.accomplishment_submission_id');

        return DB::table('accomplishment_submissions as current_submissions')
            ->joinSub($latestSubmissionRevisions, 'latest_submission_revisions', function ($join): void {
                $join->on('latest_submission_revisions.accomplishment_id', '=', 'current_submissions.accomplishment_id')
                    ->on('latest_submission_revisions.revision', '=', 'current_submissions.revision');
            })
            ->leftJoinSub($acceptedEvaluations, 'accepted_evaluation_ids', function ($join): void {
                $join->on(
                    'accepted_evaluation_ids.accomplishment_submission_id',
                    '=',
                    'current_submissions.id',
                );
            })
            ->leftJoinSub($presidentialEvaluations, 'presidential_evaluation_ids', function ($join): void {
                $join->on(
                    'presidential_evaluation_ids.accomplishment_submission_id',
                    '=',
                    'current_submissions.id',
                );
            })
            ->where(function (Builder $query): void {
                $query->whereNotNull('presidential_evaluation_ids.evaluation_id')
                    ->orWhereNotNull('accepted_evaluation_ids.evaluation_id');
            })
            ->select([
                'current_submissions.accomplishment_id',
                'current_submissions.id as accomplishment_submission_id',
            ])
            ->selectRaw(
                'COALESCE(presidential_evaluation_ids.evaluation_id, accepted_evaluation_ids.evaluation_id) AS evaluation_id',
            );
    }
}
