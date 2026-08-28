<?php

namespace App\Enums;

enum TargetOperator: string
{
    case Equals = 'equals';
    case AtLeast = 'at_least';
    case AtMost = 'at_most';
    case PercentageAtLeast = 'percentage_at_least';
    case PercentageAtMost = 'percentage_at_most';
    case ZeroTolerance = 'zero_tolerance';
    case Qualitative = 'qualitative';

    /**
     * Get the display label for the operator.
     */
    public function label(): string
    {
        return match ($this) {
            self::Equals => 'Equals',
            self::AtLeast => 'At least',
            self::AtMost => 'At most',
            self::PercentageAtLeast => 'Percentage at least',
            self::PercentageAtMost => 'Percentage at most',
            self::ZeroTolerance => 'Zero tolerance',
            self::Qualitative => 'Qualitative',
        };
    }

    public function supportsAccomplishmentPercentage(): bool
    {
        return in_array($this, [
            self::Equals,
            self::AtLeast,
            self::PercentageAtLeast,
        ], true);
    }

    /**
     * Get the operators formatted for selection controls.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $operator): array => [
                'value' => $operator->value,
                'label' => $operator->label(),
            ],
            self::cases(),
        );
    }
}
