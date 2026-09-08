<?php

namespace App\Actions\Users;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateUser
{
    public function __construct(private SyncOrganizationalAssignments $syncAssignments) {}

    /** @param array<string, mixed> $data */
    public function handle(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $assignments = $data['assignments'];
            unset($data['assignments']);
            if (! filled($data['password'] ?? null)) {
                unset($data['password']);
            }
            $role = Role::systemFor(UserRole::from($data['role']));
            $user->fill([...$data, 'role_id' => $role->id]);
            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }
            $user->save();
            $this->syncAssignments->handle($user, $assignments);

            return $user->refresh();
        });
    }
}
