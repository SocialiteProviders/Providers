<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;

/**
 * Signature verification defaults, and the transport precondition that the
 * OIDC Core 3.1.3.7 step 6 exemption rests on.
 */
class VerificationDefaultsTest extends TestCase
{
    private function providerWithoutConfigKey(array $config = []): ProviderStub
    {
        // Deliberately omits 'verify_jwt' entirely, rather than passing null.
        return $this->oidcProvider(array_merge(['verify_jwt' => null], $config));
    }

    #[Test]
    public function verification_is_on_by_default(): void
    {
        $this->seedJwks();

        $provider = $this->providerWithoutConfigKey();

        $forged = JWT::encode($this->claims(['sub' => 'admin']), $this->publicKey, 'HS256', self::KID);

        $this->expectException(InvalidArgumentException::class);

        $provider->callVerifyFromToken($forged);
    }

    #[Test]
    public function an_unsigned_payload_is_not_trusted_by_default(): void
    {
        $this->seedJwks();

        $request = $this->request(session: ['openidconnect_nonce' => 'n']);
        $provider = $this->oidcProvider(['verify_jwt' => null], $request);

        // A token the IdP never signed, claiming elevated group membership.
        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT', 'kid' => self::KID]));
        $body = $this->base64UrlEncode(json_encode($this->claims([
            'nonce'  => 'n',
            'sub'    => 'admin',
            'groups' => ['administrators'],
        ])));

        $this->expectException(InvalidArgumentException::class);

        $provider->callDecodeJWT($header.'.'.$body.'.not-a-real-signature');
    }

    #[Test]
    public function verification_can_still_be_opted_out_of(): void
    {
        // Regression cover for ConfigTrait::getConfig() treating any falsy
        // value as absent: reading via getConfig() would discard this false
        // and fall back to the now-true default, stranding these operators.
        $request = $this->request(session: ['openidconnect_nonce' => 'n']);
        $provider = $this->oidcProvider(['verify_jwt' => false], $request);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($this->claims(['nonce' => 'n', 'sub' => 'alice'])));

        $payload = $provider->callDecodeJWT($header.'.'.$body.'.unchecked');

        $this->assertSame('alice', $payload->sub);
    }

    #[Test]
    public function string_falsy_values_also_opt_out(): void
    {
        $request = $this->request(session: ['openidconnect_nonce' => 'n']);
        $provider = $this->oidcProvider(['verify_jwt' => 'false'], $request);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode($this->claims(['nonce' => 'n', 'sub' => 'alice'])));

        $this->assertSame('alice', $provider->callDecodeJWT($header.'.'.$body.'.unchecked')->sub);
    }

    #[Test]
    public function logout_tokens_are_verified_even_when_verification_is_opted_out_of(): void
    {
        $this->seedJwks();

        $provider = $this->oidcProvider(['verify_jwt' => false]);

        $header = $this->base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $body = $this->base64UrlEncode(json_encode([
            'iss'    => self::BASE_URL,
            'aud'    => self::CLIENT_ID,
            'sub'    => 'user-1',
            'jti'    => 'x',
            'iat'    => time(),
            'events' => ['http://schemas.openid.net/event/backchannel-logout' => new \stdClass],
        ]));

        $this->expectException(InvalidArgumentException::class);

        $provider->verifyLogoutToken($header.'.'.$body.'.unchecked');
    }

    #[Test]
    public function a_plaintext_base_url_is_rejected(): void
    {
        $provider = $this->oidcProvider(['base_url' => 'http://idp.test'], discovery: null);
        $provider->configurations = null;

        $this->expectExceptionMessage('must use https');

        $provider->callAllowedSigningAlgorithms();
    }

    #[Test]
    public function a_scheme_less_base_url_is_rejected(): void
    {
        $provider = $this->oidcProvider(['base_url' => 'idp.test']);
        $provider->configurations = null;

        $this->expectExceptionMessage('must use https');

        $provider->callAllowedSigningAlgorithms();
    }

    #[Test]
    public function loopback_hosts_may_use_plaintext(): void
    {
        foreach (['http://localhost:8080', 'http://127.0.0.1', 'http://app.localhost'] as $baseUrl) {
            $provider = $this->oidcProvider(['base_url' => $baseUrl]);

            $this->assertSame(rtrim($baseUrl, '/'), $provider->callGetBaseUrl());
        }
    }

    #[Test]
    public function an_https_base_url_is_accepted_and_normalised(): void
    {
        $provider = $this->oidcProvider(['base_url' => 'https://idp.test/']);

        $this->assertSame('https://idp.test', $provider->callGetBaseUrl());
    }
}
