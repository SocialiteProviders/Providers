<?php

namespace SocialiteProviders\Tests\Apple;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Apple\Provider;
use SocialiteProviders\Tests\TestCase;

class AppleProviderTest extends TestCase
{
    private const KEY_ID = 'test-key';

    protected function provider(): string
    {
        return Provider::class;
    }

    protected function tearDown(): void
    {
        Cache::clearResolvedInstance('cache');

        parent::tearDown();
    }

    public function test_identity_token_with_matching_audience_is_accepted(): void
    {
        [$privateKey, $jwks] = $this->createKeyPair();
        $this->fakeAppleJwks($jwks);

        $user = $this->makeProvider()->userByIdentityToken(
            $this->identityToken($privateKey, static::CLIENT_ID)
        );

        $this->assertSame('apple-user-id', $user->getId());
        $this->assertSame('user@example.com', $user->getEmail());
    }

    public function test_identity_token_with_different_audience_is_rejected(): void
    {
        [$privateKey, $jwks] = $this->createKeyPair();
        $this->fakeAppleJwks($jwks);

        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The token is not allowed to be used by this audience');

        $this->makeProvider()->userByIdentityToken(
            $this->identityToken($privateKey, 'another-client-id')
        );
    }

    /**
     * @return array{string, array{keys: array<int, array<string, string>>}}
     */
    private function createKeyPair(): array
    {
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $this->assertNotFalse($key);
        $this->assertTrue(openssl_pkey_export($key, $privateKey));

        $details = openssl_pkey_get_details($key);

        $this->assertIsArray($details);

        return [
            $privateKey,
            [
                'keys' => [[
                    'kty' => 'RSA',
                    'kid' => self::KEY_ID,
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n'   => $this->base64UrlEncode($details['rsa']['n']),
                    'e'   => $this->base64UrlEncode($details['rsa']['e']),
                ]],
            ],
        ];
    }

    /**
     * @param  array{keys: array<int, array<string, string>>}  $jwks
     */
    private function fakeAppleJwks(array $jwks): void
    {
        Cache::swap(new class($jwks)
        {
            public function __construct(private readonly array $jwks) {}

            public function remember(string $key, int $ttl, callable $callback): array
            {
                return $this->jwks;
            }
        });
    }

    private function identityToken(string $privateKey, string $audience): string
    {
        $now = time();

        return JWT::encode([
            'iss'   => Provider::URL,
            'sub'   => 'apple-user-id',
            'aud'   => $audience,
            'iat'   => $now - 5,
            'nbf'   => $now - 5,
            'exp'   => $now + 300,
            'email' => 'user@example.com',
        ], $privateKey, 'RS256', self::KEY_ID);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
