<?php

namespace App\Actions\Auth;

use App\Models\User;
use Exception;
use Laravel\Socialite\Facades\Socialite;

/**
 * Authenticates a customer with a Google or Apple identity token (FR-001,
 * research R-7).
 *
 * The provider-verified email is matched to an existing user — claiming a
 * previously registered account — or used to create a new customer identity,
 * and a Sanctum token is issued for that identity. Guests (anonymous users
 * with a null email) are never overwritten by a social sign-in.
 */
class AuthenticateSocialUserAction
{
    /**
     * @return array{user: User, token: string}
     */
    public function execute(string $provider, string $accessToken): array
    {
        try {
            $socialUser = Socialite::driver($provider)->userFromToken($accessToken);
        } catch (Exception $exception) {
            throw new SocialAuthFailedException(
                'The identity token could not be verified.'
            );
        }

        $email = $socialUser->getEmail();
        if ($email === null) {
            throw new SocialAuthFailedException(
                'The provider did not return a verified email address to match the account.'
            );
        }

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => $socialUser->getName() !== null && $socialUser->getName() !== ''
                    ? $socialUser->getName()
                    : $this->fallbackName($provider),
                'email_verified_at' => now(),
                'password' => null,
                'is_guest' => false,
            ],
        );

        return [
            'user' => $user,
            'token' => $user->createToken($provider)->plainTextToken,
        ];
    }

    private function fallbackName(string $provider): string
    {
        return $provider === 'apple' ? 'Apple customer' : 'Google customer';
    }
}
