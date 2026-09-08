<?php

namespace App\Enums;

enum AccomplishmentStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case Accepted = 'accepted';
    case Returned = 'returned';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::UnderReview => 'Under Review',
            self::Accepted => 'Accepted',
            self::Returned => 'Returned',
            self::Rejected => 'Rejected',
        };
    }
}
