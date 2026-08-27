<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case Reviewer = 'reviewer';
    case DepartmentUser = 'department_user';

    /**
     * Get the display label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Reviewer => 'Reviewer',
            self::DepartmentUser => 'Department User',
        };
    }

    /**
     * Get the roles formatted for selection controls.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $role): array => [
                'value' => $role->value,
                'label' => $role->label(),
            ],
            self::cases(),
        );
    }
}
