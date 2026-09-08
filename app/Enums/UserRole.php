<?php

namespace App\Enums;

enum UserRole: string
{
    case SystemAdmin = 'system_admin';
    case Executive = 'executive';
    case OfficeHead = 'office_head';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::SystemAdmin => 'System Admin',
            self::Executive => 'Executive',
            self::OfficeHead => 'Office Head',
            self::Staff => 'Secretary / Staff',
        };
    }

    public function assignmentType(): ?AssignmentType
    {
        return match ($this) {
            self::SystemAdmin => null,
            self::Executive => AssignmentType::Executive,
            self::OfficeHead => AssignmentType::Head,
            self::Staff => AssignmentType::Staff,
        };
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $role): array => [
            'value' => $role->value,
            'label' => $role->label(),
        ], self::cases());
    }
}
