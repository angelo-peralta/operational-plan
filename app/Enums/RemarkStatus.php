<?php

namespace App\Enums;

enum RemarkStatus: string
{
    case Open = 'open';
    case Addressed = 'addressed';
    case Resolved = 'resolved';

    public function label(): string
    {
        return match ($this) {
            self::Open => __('Open'),
            self::Addressed => __('Addressed'),
            self::Resolved => __('Resolved'),
        };
    }
}
