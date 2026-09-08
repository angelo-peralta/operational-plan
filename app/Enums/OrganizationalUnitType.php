<?php

namespace App\Enums;

enum OrganizationalUnitType: string
{
    case University = 'university';
    case ExecutiveOffice = 'executive_office';
    case VicePresidentialArea = 'vice_presidential_area';
    case School = 'school';
    case College = 'college';
    case Center = 'center';
    case Office = 'office';
    case Department = 'department';
    case Unit = 'unit';
    case Other = 'other';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /** @return list<array{value: string, label: string}> */
    public static function options(): array
    {
        return array_map(static fn (self $type): array => [
            'value' => $type->value, 'label' => $type->label(),
        ], self::cases());
    }
}
