<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterCustomerAction
{
    /**
     * @param  array{name: string, email: string, password: string}  $validatedData
     * @return array{user: User, token: string}
     */
    public function execute(array $validatedData): array
    {
        $user = User::create($validatedData);

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
