<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateUserAction
{
    /**
     * @param  array{email: string, password: string}  $validatedData
     * @return array{user: User, token: string}
     *
     * @throws ValidationException
     */
    public function execute(array $validatedData): array
    {
        $user = User::where('email', $validatedData['email'])->first();

        if ($user === null || ! Hash::check($validatedData['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        return [
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken,
        ];
    }
}
