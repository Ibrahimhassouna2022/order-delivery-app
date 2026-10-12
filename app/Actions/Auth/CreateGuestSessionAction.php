<?php

namespace App\Actions\Auth;

use App\Enums\UserRole;
use App\Models\User;

/**
 * Creates an anonymous guest customer identity (FR-001, research R-7).
 *
 * The account has no password or verified email. Claiming it on a later
 * Google/Apple sign-in happens through AuthenticateSocialUserAction via
 * client-held guest token merging; the unique anonymous row is created here
 * so ordering works without registration.
 */
class CreateGuestSessionAction
{
    /**
     * @return array{user: User, token: string}
     */
    public function execute(): array
    {
        $user = User::create([
            'name' => 'Guest',
            'email' => null,
            'email_verified_at' => null,
            'password' => null,
            'is_guest' => true,
        ]);

        $user->role = UserRole::Customer;

        return [
            'user' => $user,
            'token' => $user->createToken('guest')->plainTextToken,
        ];
    }
}
