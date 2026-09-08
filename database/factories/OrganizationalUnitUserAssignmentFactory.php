<?php

namespace Database\Factories;

use App\Enums\AssignmentType;
use App\Models\OrganizationalUnit;
use App\Models\OrganizationalUnitUserAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationalUnitUserAssignment> */
class OrganizationalUnitUserAssignmentFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'organizational_unit_id' => OrganizationalUnit::factory(),
            'assignment_type' => AssignmentType::Staff,
            'is_primary' => false,
            'is_active' => true,
        ];
    }
}
