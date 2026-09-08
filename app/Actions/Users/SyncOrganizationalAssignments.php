<?php

namespace App\Actions\Users;

use App\Models\User;

class SyncOrganizationalAssignments
{
    /** @param list<array<string, mixed>> $assignments */
    public function handle(User $user, array $assignments): void
    {
        $user->organizationalUnitAssignments()->update(['is_active' => false, 'is_primary' => false]);
        foreach ($assignments as $assignment) {
            $user->organizationalUnitAssignments()->updateOrCreate(
                ['organizational_unit_id' => $assignment['organizational_unit_id']],
                $assignment,
            );
        }
        $user->unsetRelation('organizationalUnitAssignments');
    }
}
