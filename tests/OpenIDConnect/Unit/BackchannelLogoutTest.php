<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use SocialiteProviders\OpenIDConnect\IssuerValidators\EntraIssuerValidator;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class BackchannelLogoutTest extends TestCase
{
    use InteractsWithOidc;

    private function logoutTokenClaims(array $overrides = []): array
    {
        return array_merge([
            'iss'    => static::$opBaseUrl,
            'aud'    => static::$opClientId,
            'iat'    => time(),
            'exp'    => time() + 120,
            'jti'    => 'jti-'.bin2hex(random_bytes(8)),
            'sid'    => 'idp-session-1',
            'sub'    => 'user-123',
            'events' => ['http://schemas.openid.net/event/backchannel-logout' => (object) []],
        ], $overrides);
    }

    /**
     * Discovery + JWKS, the two calls verifyLogoutToken() needs.
     */
    private function backchannelResponses(): array
    {
        return [
            $this->jsonResponse($this->discoveryDocument()),
            $this->jsonResponse($this->jwksDocument()),
        ];
    }

    public function test_a_valid_logout_token_returns_its_claims(): void
    {
        $provider = $this->makeProvider([], $this->backchannelResponses());

        $claims = $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
            'jti' => 'jti-valid',
        ])));

        $this->assertSame('idp-session-1', $claims['sid']);
        $this->assertSame('user-123', $claims['sub']);
    }

    public function test_logout_tokens_are_verified_even_when_verify_jwt_is_false(): void
    {
        // The id_token TLS exemption never applies to an unsolicited POST.
        $provider = $this->makeProvider(['verify_jwt' => false], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Verification failed');

        $provider->verifyLogoutToken($this->unsignedToken($this->logoutTokenClaims()));
    }

    public function test_a_replayed_jti_is_rejected(): void
    {
        $token = $this->encodeToken($this->logoutTokenClaims(['jti' => 'jti-replay']));

        $provider = $this->makeProvider([], $this->backchannelResponses());
        $provider->verifyLogoutToken($token);

        $again = $this->makeProvider([], []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already used');

        $again->verifyLogoutToken($token);
    }

    public function test_replay_protection_can_be_opted_out_of(): void
    {
        $token = $this->encodeToken($this->logoutTokenClaims(['jti' => 'jti-optout']));

        $provider = $this->makeProvider(['logout_token_replay_ttl' => 0], $this->backchannelResponses());
        $provider->verifyLogoutToken($token);

        $again = $this->makeProvider(['logout_token_replay_ttl' => 0], []);

        $this->assertSame('user-123', $again->verifyLogoutToken($token)['sub']);
    }

    public function test_a_missing_events_claim_is_rejected(): void
    {
        $claims = $this->logoutTokenClaims();
        unset($claims['events']);

        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('backchannel-logout event');

        $provider->verifyLogoutToken($this->encodeToken($claims));
    }

    public function test_a_nonce_is_forbidden_in_logout_tokens(): void
    {
        // Back-Channel Logout 2.4: a nonce marks an id_token; refusing it
        // prevents token-type confusion.
        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must not contain a nonce');

        $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
            'nonce' => 'some-nonce',
        ])));
    }

    public function test_a_missing_jti_is_rejected(): void
    {
        $claims = $this->logoutTokenClaims();
        unset($claims['jti']);

        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('missing jti');

        $provider->verifyLogoutToken($this->encodeToken($claims));
    }

    public function test_a_missing_iat_is_rejected(): void
    {
        $claims = $this->logoutTokenClaims();
        unset($claims['iat']);

        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('iat');

        $provider->verifyLogoutToken($this->encodeToken($claims));
    }

    public function test_a_token_with_neither_sub_nor_sid_is_rejected(): void
    {
        $claims = $this->logoutTokenClaims();
        unset($claims['sub'], $claims['sid']);

        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('sub and/or sid');

        $provider->verifyLogoutToken($this->encodeToken($claims));
    }

    public function test_a_logout_token_from_another_issuer_is_rejected(): void
    {
        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid issuer');

        $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
            'iss' => 'https://evil.test',
        ])));
    }

    public function test_a_logout_token_for_another_client_is_rejected(): void
    {
        $provider = $this->makeProvider([], $this->backchannelResponses());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid audience');

        $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
            'aud' => 'someone-else',
        ])));
    }

    public function test_an_expired_logout_token_is_rejected(): void
    {
        $provider = $this->makeProvider([], $this->backchannelResponses());

        try {
            $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
                'exp' => time() - 120,
                'iat' => time() - 240,
            ])));
            $this->fail('Expected the expired logout token to be rejected.');
        } catch (InvalidArgumentException $e) {
            $this->assertSame(401, $e->getCode());
            $this->assertStringContainsStringIgnoringCase('expired', $e->getMessage());
        }
    }

    public function test_the_configured_issuer_validator_applies_to_logout_tokens_too(): void
    {
        $provider = $this->makeProvider(
            [
                'issuer'           => 'https://login.microsoftonline.com/{tenantid}/v2.0',
                'issuer_validator' => EntraIssuerValidator::class,
            ],
            $this->backchannelResponses(),
        );

        $claims = $provider->verifyLogoutToken($this->encodeToken($this->logoutTokenClaims([
            'iss' => 'https://login.microsoftonline.com/tenant-123/v2.0',
            'tid' => 'tenant-123',
        ])));

        $this->assertSame('user-123', $claims['sub']);
    }
}
