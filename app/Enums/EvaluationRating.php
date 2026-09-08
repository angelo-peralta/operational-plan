<?php

namespace App\Enums;

enum EvaluationRating: int
{
    case DidNotMeet = 0;
    case SomehowMet = 1;
    case Met = 2;
    case Exceeded = 3;

    public function label(): string
    {
        return match ($this) {
            self::DidNotMeet => 'Did Not Meet Requirements',
            self::SomehowMet => 'Somehow Met Requirements',
            self::Met => 'Met Requirements',
            self::Exceeded => 'Exceeded Requirements',
        };
    }

    /** @return list<array{value: int, label: string}> */
    public static function options(): array
    {
        return array_map(
            static fn (self $rating): array => [
                'value' => $rating->value,
                'label' => $rating->label(),
            ],
            self::cases(),
        );
    }
}
