<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use PHPUnit\Framework\Attributes\Test;

class IdTokenClaimsTest extends TestCase
{
    protected const NONCE = 'test-nonce-value';

    private function providerWithNonce(array $config = [], ?array $discovery = null): ProviderStub
    {
        $this->seedJwks();

        return $this->oidcProvider(
            $config,
            $this->request(session: ['openidconnect_nonce' => self::NONCE]),
            $discovery
        );
    }

    #[Test]
    public function a_valid_token_is_accepted(): void
    {
        $provider = $this->providerWithNonce();

        $payload = $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE]));

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function the_nonce_is_cleared_once_consumed(): void
    {
        $request = $this->request(session: ['openidconnect_nonce' => self::NONCE]);
        $this->seedJwks();
        $provider = $this->oidcProvider([], $request);

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE]));

        $this->assertNull($request->session()->get('openidconnect_nonce'));
    }

    #[Test]
    public function a_mismatched_nonce_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('invalid nonce');

        $provider->callDecodeJWT($this->idToken(['nonce' => 'attacker-nonce']));
    }

    #[Test]
    public function a_missing_nonce_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('invalid nonce');

        $provider->callDecodeJWT($this->idToken());
    }

    #[Test]
    public function a_mismatched_issuer_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('Invalid issuer');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'iss' => 'https://evil.test']));
    }

    #[Test]
    public function the_issuer_config_overrides_discovery(): void
    {
        $provider = $this->providerWithNonce(['issuer' => 'https://override.test']);

        $payload = $provider->callDecodeJWT($this->idToken([
            'nonce' => self::NONCE,
            'iss'   => 'https://override.test',
        ]));

        $this->assertSame('https://override.test', $payload->iss);
    }

    #[Test]
    public function a_token_for_another_audience_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('Invalid audience');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'aud' => 'another-client']));
    }

    #[Test]
    public function an_audience_array_containing_the_client_id_is_accepted(): void
    {
        $provider = $this->providerWithNonce();

        $payload = $provider->callDecodeJWT($this->idToken([
            'nonce' => self::NONCE,
            'aud'   => [self::CLIENT_ID],
        ]));

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function a_multi_audience_token_requires_a_matching_azp(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('authorized party');

        $provider->callDecodeJWT($this->idToken([
            'nonce' => self::NONCE,
            'aud'   => [self::CLIENT_ID, 'another-client'],
            'azp'   => 'another-client',
        ]));
    }

    #[Test]
    public function an_expired_token_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        // php-jwt rejects this during signature verification ("Expired token");
        // validateTimeClaims() is the backstop when verification is disabled.
        $this->expectExceptionMessageMatches('/expired/i');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'exp' => time() - 3600]));
    }

    #[Test]
    public function an_expired_token_is_rejected_even_when_signature_verification_is_off(): void
    {
        $request = $this->request(session: ['openidconnect_nonce' => self::NONCE]);
        $provider = $this->oidcProvider(['verify_jwt' => false], $request);

        $this->expectExceptionMessage('expired');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'exp' => time() - 3600]));
    }

    #[Test]
    public function clock_skew_tolerates_a_marginally_expired_token(): void
    {
        $provider = $this->providerWithNonce(['clock_skew' => 300]);

        $payload = $provider->callDecodeJWT($this->idToken([
            'nonce' => self::NONCE,
            'exp'   => time() - 30,
        ]));

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function a_token_that_is_not_yet_valid_is_rejected(): void
    {
        $request = $this->request(session: ['openidconnect_nonce' => self::NONCE]);
        $provider = $this->oidcProvider(['verify_jwt' => false], $request);

        $this->expectExceptionMessage('not yet valid');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'nbf' => time() + 3600]));
    }

    #[Test]
    public function a_token_issued_in_the_future_is_rejected(): void
    {
        $request = $this->request(session: ['openidconnect_nonce' => self::NONCE]);
        $provider = $this->oidcProvider(['verify_jwt' => false], $request);

        $this->expectExceptionMessage('issued in the future');

        $provider->callDecodeJWT($this->idToken(['nonce' => self::NONCE, 'iat' => time() + 3600]));
    }

    #[Test]
    public function a_valid_at_hash_is_accepted(): void
    {
        $provider = $this->providerWithNonce();
        $accessToken = 'the-access-token';

        $payload = $provider->callDecodeJWT(
            $this->idToken(['nonce' => self::NONCE, 'at_hash' => $this->atHash($accessToken)]),
            $accessToken
        );

        $this->assertSame('user-1', $payload->sub);
    }

    #[Test]
    public function a_mismatched_at_hash_is_rejected(): void
    {
        $provider = $this->providerWithNonce();

        $this->expectExceptionMessage('at_hash mismatch');

        $provider->callDecodeJWT(
            $this->idToken(['nonce' => self::NONCE, 'at_hash' => $this->atHash('a-different-token')]),
            'the-access-token'
        );
    }

    private function atHash(string $accessToken): string
    {
        $digest = hash('sha256', $accessToken, true);

        return $this->base64UrlEncode(substr($digest, 0, intdiv(strlen($digest), 2)));
    }
}
