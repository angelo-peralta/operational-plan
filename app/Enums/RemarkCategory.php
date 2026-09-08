<?php

namespace App\Enums;

enum RemarkCategory: string
{
    case Correction = 'correction';
    case Clarification = 'clarification';
    case Recommendation = 'recommendation';
    case ApprovalCondition = 'approval_condition';
    case General = 'general';
}
