<?php

namespace App\Enums;

enum EvaluationDecision: string
{
    case Accepted = 'accepted';
    case RequestRevision = 'request_revision';
    case RequestAdditionalEvidence = 'request_additional_evidence';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Accepted => 'Accepted',
            self::RequestRevision => 'Request Revision',
            self::RequestAdditionalEvidence => 'Request Additional Evidence',
            self::Rejected => 'Rejected',
        };
    }

    public function accomplishmentStatus(): AccomplishmentStatus
    {
        return match ($this) {
            self::Accepted => AccomplishmentStatus::Accepted,
            self::RequestRevision, self::RequestAdditionalEvidence => AccomplishmentStatus::Returned,
            self::Rejected => AccomplishmentStatus::Rejected,
        };
    }

    public function requiresRating(): bool
    {
        return $this === self::Accepted;
    }

    public function permitsRating(): bool
    {
        return ! in_array($this, [self::RequestRevision, self::RequestAdditionalEvidence], true);
    }

    public function requiresRemarks(?EvaluationRating $rating): bool
    {
        return $this !== self::Accepted
            || in_array($rating, [EvaluationRating::SomehowMet, EvaluationRating::DidNotMeet], true);
    }
}
