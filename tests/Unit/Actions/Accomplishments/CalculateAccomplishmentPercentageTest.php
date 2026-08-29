<?php

use App\Actions\Accomplishments\CalculateAccomplishmentPercentage;
use App\Enums\TargetOperator;
use App\Models\PlanItem;

function planItemWithNumericTarget(
    ?string $targetValue,
    ?TargetOperator $targetOperator = null,
): PlanItem {
    return new PlanItem([
        'target_value' => $targetValue,
        'target_operator' => $targetOperator,
    ]);
}

it('calculates percentages for supported numeric target operators', function (?TargetOperator $operator) {
    $percentage = (new CalculateAccomplishmentPercentage)->handle(
        planItemWithNumericTarget('20', $operator),
        '15',
    );

    expect($percentage)->toBe('75.0000');
})->with([
    'unspecified operator' => null,
    'equals' => TargetOperator::Equals,
    'at least' => TargetOperator::AtLeast,
    'percentage at least' => TargetOperator::PercentageAtLeast,
]);

it('does not calculate percentages for qualitative or inverse numeric targets', function (TargetOperator $operator) {
    $percentage = (new CalculateAccomplishmentPercentage)->handle(
        planItemWithNumericTarget('20', $operator),
        '15',
    );

    expect($percentage)->toBeNull();
})->with([
    'at most' => TargetOperator::AtMost,
    'percentage at most' => TargetOperator::PercentageAtMost,
    'zero tolerance' => TargetOperator::ZeroTolerance,
    'qualitative' => TargetOperator::Qualitative,
]);

it('does not calculate a percentage without a positive target and reported value', function (?string $targetValue, ?string $reportedValue) {
    $percentage = (new CalculateAccomplishmentPercentage)->handle(
        planItemWithNumericTarget($targetValue, TargetOperator::AtLeast),
        $reportedValue,
    );

    expect($percentage)->toBeNull();
})->with([
    'missing target' => [null, '5'],
    'missing reported value' => ['10', null],
    'zero target' => ['0', '5'],
    'negative target' => ['-10', '5'],
]);

it('rounds calculated percentages half up to four decimal places', function () {
    $percentage = (new CalculateAccomplishmentPercentage)->handle(
        planItemWithNumericTarget('6', TargetOperator::AtLeast),
        '1',
    );

    expect($percentage)->toBe('16.6667');
});

it('does not cap accomplishments that exceed the numeric target', function () {
    $percentage = (new CalculateAccomplishmentPercentage)->handle(
        planItemWithNumericTarget('20', TargetOperator::AtLeast),
        '30',
    );

    expect($percentage)->toBe('150.0000');
});
