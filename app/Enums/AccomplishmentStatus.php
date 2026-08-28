<?php

namespace App\Enums;

enum AccomplishmentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case QueuedForAnalysis = 'queued_for_analysis';
    case Analyzing = 'analyzing';
    case ReadyForReview = 'ready_for_review';
    case Accepted = 'accepted';
    case Returned = 'returned';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::QueuedForAnalysis => 'Queued for Analysis',
            self::Analyzing => 'Analyzing',
            self::ReadyForReview => 'Ready for Review',
            self::Accepted => 'Accepted',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
        };
    }
}
