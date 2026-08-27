<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUser
{
    /**
     * Update an administratively managed user.
     *
     * @param  array{name: string, email: string, password?: string|null, role: string, department_id: int|null}  $data
     */
    public function handle(User $user, array $data): User
    {
        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return $user->refresh();
    }
}
