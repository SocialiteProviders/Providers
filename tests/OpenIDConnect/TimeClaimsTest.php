<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;

/**
 * exp is required, not optional. php-jwt gates its own exp check on isset(),
 * so a token that omits the claim is otherwise valid forever on both the
 * verified and unverified paths.
 */
class TimeClaimsTest extends TestCase
{
    private const NONCE = 'test-nonce-value';

    /**
     * claims() always supplies exp/iat, so build the payload by hand here.
     */
    private function tokenWithout(array $drop, array $extra = []): string
    {
        $claims = array_merge([
            'iss'   => self::BASE_URL,
            'aud'   => self::CLIENT_ID,
            'sub'   => 'user-1',
            'email' => 'user@example.test',
            'nonce' => self::NONCE,
            'iat'   => time(),
            'exp'   => time() + 3600,
        ], $extra);

        foreach ($drop as $claim) {
            unset($claims[$claim]);
        }

        return JWT::encode($claims, $this->privateKey, 'RS256', self::KID);
    }

    #[Test]
    public function an_id_token_without_exp_is_rejected_on_the_verified_path(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider([], $this->request(session: ['openidconnect_nonce' => self::NONCE]));

        $this->expectExceptionMessage('Missing required exp');

        $provider->callDecodeJWT($this->tokenWithout(['exp']));
    }

    #[Test]
    public function an_id_token_without_exp_is_rejected_on_the_unverified_path(): void
    {
        // The replay case: with verification off there is nothing else
        // bounding the token's lifetime.
        $provider = $this->oidcProvider(
            ['verify_jwt' => false],
            $this->request(session: ['openidconnect_nonce' => self::NONCE])
        );

        $this->expectExceptionMessage('Missing required exp');

        $provider->callDecodeJWT($this->tokenWithout(['exp']));
    }

    #[Test]
    public function a_non_numeric_exp_is_rejected(): void
    {
        $provider = $this->oidcProvider(
            ['verify_jwt' => false],
            $this->request(session: ['openidconnect_nonce' => self::NONCE])
        );

        // JWT::encode() rejects a non-numeric exp itself, so this token has to
        // be assembled by hand -- which is exactly what an attacker would do.
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode([
            'iss'   => self::BASE_URL,
            'aud'   => self::CLIENT_ID,
            'sub'   => 'user-1',
            'email' => 'user@example.test',
            'nonce' => self::NONCE,
            'exp'   => 'not-a-timestamp',
        ]));

        $this->expectExceptionMessage('Missing required exp');

        $provider->callDecodeJWT($header.'.'.$body.'.unchecked');
    }

    #[Test]
    public function an_id_token_with_exp_is_still_accepted(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider([], $this->request(session: ['openidconnect_nonce' => self::NONCE]));

        $this->assertSame('user-1', $provider->callDecodeJWT($this->tokenWithout([]))->sub);
    }

    #[Test]
    public function an_id_token_without_iat_is_still_accepted(): void
    {
        // Only the logout token requires iat; leaving the id_token unchanged
        // here keeps this fix to what the spec actually mandates.
        $this->seedJwks();

        $provider = $this->oidcProvider([], $this->request(session: ['openidconnect_nonce' => self::NONCE]));

        $this->assertSame('user-1', $provider->callDecodeJWT($this->tokenWithout(['iat']))->sub);
    }

    #[Test]
    public function a_logout_token_without_exp_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('Missing required exp');

        $this->oidcProvider()->verifyLogoutToken($this->logoutTokenWithout(['exp']));
    }

    #[Test]
    public function a_logout_token_without_iat_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('Missing required iat');

        $this->oidcProvider()->verifyLogoutToken($this->logoutTokenWithout(['iat']));
    }

    #[Test]
    public function a_complete_logout_token_is_still_accepted(): void
    {
        $this->seedJwks();

        $payload = $this->oidcProvider()->verifyLogoutToken($this->logoutTokenWithout([]));

        $this->assertSame('user-1', $payload['sub']);
    }

    private function logoutTokenWithout(array $drop): string
    {
        $claims = [
            'iss'    => self::BASE_URL,
            'aud'    => self::CLIENT_ID,
            'sub'    => 'user-1',
            'sid'    => 'session-1',
            'iat'    => time(),
            'exp'    => time() + 300,
            'jti'    => 'unique-token-id',
            'events' => ['http://schemas.openid.net/event/backchannel-logout' => new \stdClass],
        ];

        foreach ($drop as $claim) {
            unset($claims[$claim]);
        }

        return JWT::encode($claims, $this->privateKey, 'RS256', self::KID);
    }
}
