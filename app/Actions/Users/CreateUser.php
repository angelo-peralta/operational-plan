<?php

namespace App\Actions\Users;

use App\Actions\Teams\CreateTeam;
use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function __construct(private CreateTeam $createTeam, private SyncOrganizationalAssignments $syncAssignments) {}

    /** @param array<string, mixed> $data */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $assignments = $data['assignments'];
            unset($data['assignments']);
            $role = Role::systemFor(UserRole::from($data['role']));
            $user = User::query()->create([...$data, 'role_id' => $role->id]);
            $this->syncAssignments->handle($user, $assignments);
            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user->refresh();
        });
    }
}
