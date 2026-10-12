<?php

namespace App\Actions\Auth;

use Exception;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Arr;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User as SocialiteUser;

/**
 * "Sign in with Apple" provider for Laravel Socialite (FR-001, research R-7).
 *
 * `laravel/socialite` v5 removed its bundled Apple driver, so this provider
 * is registered under the `apple` name via `Socialite::extend()` in
 * AppServiceProvider. The client-supplied identity token (a JWT) is verified
 * against Apple's public JSON Web Key Set, then the claims are mapped to a
 * Socialite user: `sub` → id, `email` → email (present only when the customer
 * grants it to the app).
 *
 * @see https://developer.apple.com/documentation/sign_in_with_apple
 */
class AppleProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * Apple's public JSON Web Key Set endpoint.
     */
    protected const APPLE_JWKS_URL = 'https://appleid.apple.com/auth/keys';

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://appleid.apple.com/auth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://appleid.apple.com/auth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        try {
            $claims = (array) JWT::decode($token, $this->getApplePublicKeys());
        } catch (Exception $exception) {
            throw new Exception('Failed to verify Apple identity token: '.$exception->getMessage());
        }

        if (($claims['iss'] ?? null) !== 'https://appleid.apple.com') {
            throw new Exception('Invalid Apple identity token issuer.');
        }

        if (($claims['aud'] ?? null) !== $this->clientId) {
            throw new Exception('Invalid Apple identity token audience.');
        }

        return $claims;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new SocialiteUser)->setRaw($user)->map([
            'id' => Arr::get($user, 'sub'),
            'name' => Arr::get($user, 'name', 'Apple customer'),
            'email' => Arr::get($user, 'email'),
            'avatar' => null,
        ]);
    }

    /**
     * Fetch and parse Apple's JSON Web Key Set, keyed by `kid`.
     *
     * @return array<string, \OpenSSLAsymmetricKey|string>
     */
    protected function getApplePublicKeys(): array
    {
        $response = $this->getHttpClient()->get(self::APPLE_JWKS_URL);

        $jwks = json_decode((string) $response->getBody(), true);

        if (! is_array($jwks) || ! isset($jwks['keys'])) {
            throw new Exception('Unable to fetch Apple public keys.');
        }

        return JWK::parseKeySet($jwks);
    }
}
