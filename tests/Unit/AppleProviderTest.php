<?php

namespace Tests\Unit;

use App\Actions\Auth\AppleProvider;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use phpseclib4\Crypt\EC;
use PHPUnit\Framework\TestCase;

/**
 * Exercises the real Apple identity-token verification path of
 * `App\Actions\Auth\AppleProvider` (FR-001): JWKS fetch, ES256 signature
 * verification, issuer/audience checks, and claim mapping.
 *
 * The EC key pair is generated with phpseclib (pure PHP) because the Windows
 * OpenSSL 3 build in this environment cannot generate EC keys without an
 * external `openssl.cnf`; the verify path itself still runs through
 * `firebase/php-jwt` + OpenSSL exactly as it does in production.
 */
class AppleProviderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private static ?string $privateKeyPem = null;

    /** @var array<int, array<string, string>> */
    private static array $jwks = [];

    public static function setUpBeforeClass(): void
    {
        $keyPair = EC::createKey('secp256r1');

        $jwks = json_decode($keyPair->getPublicKey()->toString('JWK'), true);
        $jwks['keys'][0]['kid'] = 'apple-test-key-1';
        $jwks['keys'][0]['alg'] = 'ES256';

        self::$jwks = $jwks;
        self::$privateKeyPem = $keyPair->toString('PKCS8');
    }

    public function test_user_from_token_verifies_and_maps_an_apple_identity_token(): void
    {
        $provider = $this->providerWithFakeJwks();

        $identityToken = $this->identityToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.delivery',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
            'email_verified' => true,
        ]);

        $user = $provider->userFromToken($identityToken);

        $this->assertSame('apple-user-001', $user->getId());
        $this->assertSame('jane@example.com', $user->getEmail());
        $this->assertSame($identityToken, $user->token);
    }

    public function test_user_from_token_rejects_a_token_for_another_audience(): void
    {
        $provider = $this->providerWithFakeJwks();

        $identityToken = $this->identityToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.somebody.else',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Apple identity token audience.');

        $provider->userFromToken($identityToken);
    }

    public function test_user_from_token_rejects_a_token_with_a_wrong_issuer(): void
    {
        $provider = $this->providerWithFakeJwks();

        $identityToken = $this->identityToken([
            'iss' => 'https://evil.example.com',
            'aud' => 'com.example.delivery',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid Apple identity token issuer.');

        $provider->userFromToken($identityToken);
    }

    public function test_user_from_token_rejects_a_tampered_token(): void
    {
        $provider = $this->providerWithFakeJwks();

        $identityToken = $this->identityToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.delivery',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
        ]);

        $tampered = substr($identityToken, 0, -4).'AAAA';

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to verify Apple identity token:');

        $provider->userFromToken($tampered);
    }

    public function test_user_from_token_rejects_an_expired_token(): void
    {
        $provider = $this->providerWithFakeJwks();

        $identityToken = $this->identityToken([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.delivery',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
            'exp' => time() - 3600,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to verify Apple identity token:');

        $provider->userFromToken($identityToken);
    }

    private function providerWithFakeJwks(): AppleProvider
    {
        $provider = new AppleProvider(
            new Request,
            'com.example.delivery',
            'client-secret',
            'https://app.example/auth/apple/callback',
        );

        $httpClient = Mockery::mock(Client::class);
        $httpClient->shouldReceive('get')
            ->once()
            ->with('https://appleid.apple.com/auth/keys')
            ->andReturn(new Response(200, [], json_encode(self::$jwks)));

        $provider->setHttpClient($httpClient);

        return $provider;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function identityToken(array $overrides = []): string
    {
        $now = time();

        $claims = array_merge([
            'iss' => 'https://appleid.apple.com',
            'aud' => 'com.example.delivery',
            'sub' => 'apple-user-001',
            'email' => 'jane@example.com',
            'iat' => $now,
            'exp' => $now + 3600,
        ], $overrides);

        return JWT::encode($claims, self::$privateKeyPem, 'ES256', 'apple-test-key-1');
    }
}
