<?php

namespace App\Actions\Accomplishments;

use App\Models\PlanItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

class CalculateAccomplishmentPercentage
{
    public function handle(PlanItem $planItem, ?string $reportedValue): ?string
    {
        if ($reportedValue === null || $planItem->target_value === null) {
            return null;
        }

        if ($planItem->target_operator !== null
            && ! $planItem->target_operator->supportsAccomplishmentPercentage()) {
            return null;
        }

        $targetValue = BigDecimal::of($planItem->target_value);

        if ($targetValue->isNegativeOrZero()) {
            return null;
        }

        return (string) BigDecimal::of($reportedValue)
            ->multipliedBy(100)
            ->dividedBy($targetValue, 4, RoundingMode::HalfUp);
    }
}
