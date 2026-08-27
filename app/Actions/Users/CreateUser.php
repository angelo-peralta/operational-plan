<?php

namespace App\Actions\Users;

use App\Actions\Teams\CreateTeam;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUser
{
    public function __construct(private CreateTeam $createTeam) {}

    /**
     * Create an administratively managed user with a personal starter-kit team.
     *
     * @param  array{name: string, email: string, password: string, role: string, department_id: int|null}  $data
     */
    public function handle(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::query()->create($data);

            $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);

            return $user->refresh();
        });
    }
}
