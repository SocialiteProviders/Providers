<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\Test;

/**
 * Back-channel logout tokens arrive on an unauthenticated endpoint, so the
 * signature and claim rules are the only thing standing in the way.
 *
 * @see https://openid.net/specs/openid-connect-backchannel-1_0.html
 */
class LogoutTokenTest extends TestCase
{
    private const EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    private function logoutToken(array $overrides = [], string $alg = 'RS256', mixed $key = null): string
    {
        $claims = array_merge([
            'iss'    => self::BASE_URL,
            'aud'    => self::CLIENT_ID,
            'sub'    => 'user-1',
            'sid'    => 'session-1',
            'iat'    => time(),
            'exp'    => time() + 300,
            'jti'    => 'unique-token-id',
            'events' => [self::EVENT => new \stdClass],
        ], $overrides);

        return JWT::encode($claims, $key ?? $this->privateKey, $alg, self::KID);
    }

    #[Test]
    public function a_valid_logout_token_is_accepted(): void
    {
        $this->seedJwks();

        $payload = $this->oidcProvider()->verifyLogoutToken($this->logoutToken());

        $this->assertSame('user-1', $payload['sub']);
        $this->assertSame('session-1', $payload['sid']);
    }

    #[Test]
    public function a_logout_token_forged_with_the_public_key_is_rejected(): void
    {
        // The attack that matters most here: this endpoint has no session and
        // no user, so the signature is the sole control.
        $provider = $this->oidcProvider(['jwt_public_key' => $this->publicKey]);

        $forged = $this->logoutToken(['sub' => 'admin'], 'HS256', $this->publicKey);

        $this->expectExceptionMessageMatches('/algorithm/i');

        $provider->verifyLogoutToken($forged);
    }

    #[Test]
    public function a_logout_token_from_another_issuer_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('invalid issuer');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['iss' => 'https://evil.test']));
    }

    #[Test]
    public function a_logout_token_for_another_audience_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('invalid audience');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['aud' => 'another-client']));
    }

    #[Test]
    public function a_logout_token_without_a_jti_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('missing jti');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['jti' => null]));
    }

    #[Test]
    public function a_logout_token_containing_a_nonce_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('must not contain a nonce');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['nonce' => 'should-not-be-here']));
    }

    #[Test]
    public function a_logout_token_without_the_backchannel_event_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('missing backchannel-logout event');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['events' => new \stdClass]));
    }

    #[Test]
    public function a_logout_token_without_sub_or_sid_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessage('must contain sub and/or sid');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['sub' => null, 'sid' => null]));
    }

    #[Test]
    public function an_expired_logout_token_is_rejected(): void
    {
        $this->seedJwks();

        $this->expectExceptionMessageMatches('/expired/i');

        $this->oidcProvider()->verifyLogoutToken($this->logoutToken(['exp' => time() - 3600]));
    }
}
