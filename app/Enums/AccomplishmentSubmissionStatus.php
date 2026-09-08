<?php

namespace App\Enums;

enum AccomplishmentSubmissionStatus: string
{
    case QueuedForAnalysis = 'queued_for_analysis';
    case Analyzing = 'analyzing';
    case ReadyForReview = 'ready_for_review';
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Returned = 'returned';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::QueuedForAnalysis => 'Queued for Analysis (historical)',
            self::Analyzing => 'Analyzing (historical)',
            self::ReadyForReview => 'Ready for Review (historical)',
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Accepted => 'Accepted',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
        };
    }
}
