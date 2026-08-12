<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use SocialiteProviders\OpenIDConnect\Provider;

/**
 * A back-channel logout token is delivered by the IdP, which retries. Acting
 * on the same jti twice has to be refused, and the window for remembering it
 * has to be tunable -- which means `logout_token_replay_ttl` must actually
 * reach the provider's config.
 */
class LogoutTokenReplayTest extends TestCase
{
    private const EVENT = 'http://schemas.openid.net/event/backchannel-logout';

    private function logoutToken(array $overrides = []): string
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

        return JWT::encode($claims, $this->privateKey, 'RS256', self::KID);
    }

    #[Test]
    public function logout_token_replay_ttl_is_an_accepted_config_key(): void
    {
        // Without this the option is silently dropped before getConfig() ever
        // sees it, and every value below would resolve to the default.
        $this->assertContains('logout_token_replay_ttl', Provider::additionalConfigKeys());
    }

    #[Test]
    public function replaying_a_logout_token_is_refused(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider();
        $token = $this->logoutToken();

        $this->assertSame('user-1', $provider->verifyLogoutToken($token)['sub']);

        $this->expectExceptionMessage('already used');

        $provider->verifyLogoutToken($token);
    }

    #[Test]
    public function a_zero_ttl_opts_out_of_replay_protection(): void
    {
        // For deployments that track jti themselves.
        $this->seedJwks();

        $provider = $this->oidcProvider(['logout_token_replay_ttl' => 0]);
        $token = $this->logoutToken();

        $this->assertSame('user-1', $provider->verifyLogoutToken($token)['sub']);
        $this->assertSame('user-1', $provider->verifyLogoutToken($token)['sub']);
    }

    #[Test]
    public function a_configured_ttl_overrides_the_value_derived_from_exp(): void
    {
        $provider = $this->oidcProvider(['logout_token_replay_ttl' => 42]);

        $this->assertSame(42, $provider->callLogoutTokenReplayTtl(time() + 100000));
    }

    #[Test]
    public function the_ttl_otherwise_outlives_the_token(): void
    {
        $provider = $this->oidcProvider(['clock_skew' => 30]);

        // 300s remaining + 30s skew + 60s margin.
        $this->assertSame(390, $provider->callLogoutTokenReplayTtl(time() + 300));
    }

    #[Test]
    public function a_token_without_exp_gets_a_fixed_window(): void
    {
        $this->assertSame(900, $this->oidcProvider()->callLogoutTokenReplayTtl(null));
    }

    #[Test]
    public function two_different_tokens_do_not_collide(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider();

        $provider->verifyLogoutToken($this->logoutToken(['jti' => 'first']));

        try {
            $provider->verifyLogoutToken($this->logoutToken(['jti' => 'second']));
        } catch (InvalidArgumentException $e) {
            $this->fail('A distinct jti was treated as a replay: '.$e->getMessage());
        }

        $this->addToAssertionCount(1);
    }
}
