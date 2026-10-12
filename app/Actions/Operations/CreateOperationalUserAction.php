<?php

namespace App\Actions\Operations;

use App\Actions\Audit\RecordAuditAction;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateOperationalUserAction
{
    public function __construct(private RecordAuditAction $audit) {}

    /**
     * @param  array{name: string, email: string, password: string}  $validatedData
     */
    public function execute(array $validatedData, UserRole $role, ?User $actor = null): User
    {
        return DB::transaction(function () use ($validatedData, $role, $actor): User {
            $user = new User($validatedData);
            $user->role = $role;
            $user->save();

            if ($role === UserRole::Driver) {
                $user->driverProfile()->create([
                    'is_available' => false,
                ]);
            }

            $this->audit->execute('user.created', $user, $actor, ['role' => $role->value]);

            return $user;
        });
    }
}
