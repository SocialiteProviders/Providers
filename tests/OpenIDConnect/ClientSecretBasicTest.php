<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use SocialiteProviders\Manager\Config;

/**
 * RFC 6749 2.3.1 requires the client id and secret to be form-urlencoded
 * before they are joined and base64'd for the Basic header. Guzzle's `auth`
 * option base64s the raw pair, which only agrees for alphanumeric credentials.
 */
class ClientSecretBasicTest extends TestCase
{
    private function providerWithCredentials(string $clientId, string $clientSecret): ProviderStub
    {
        $provider = new ProviderStub($this->request(), $clientId, $clientSecret, self::REDIRECT);

        $provider->setConfig(new Config($clientId, $clientSecret, self::REDIRECT, [
            'base_url'          => self::BASE_URL,
            'token_auth_method' => 'client_secret_basic',
        ]));

        $provider->configurations = $this->discoveryDocument();

        return $provider;
    }

    private function authorizationHeader(ProviderStub $provider): string
    {
        return $provider->callTokenRequestOptions(['code' => 'c'])[RequestOptions::HEADERS]['Authorization'];
    }

    #[Test]
    public function a_secret_containing_plus_and_slash_is_encoded(): void
    {
        // The common case: a base64-derived secret.
        $provider = $this->providerWithCredentials('client-id', 'abc+def/ghi=');

        $this->assertSame(
            'Basic '.base64_encode('client-id:abc%2Bdef%2Fghi%3D'),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function the_raw_pair_is_not_what_gets_sent(): void
    {
        $secret = 'abc+def/ghi=';
        $provider = $this->providerWithCredentials('client-id', $secret);

        $this->assertNotSame(
            'Basic '.base64_encode('client-id:'.$secret),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function a_secret_containing_a_space_is_encoded(): void
    {
        $provider = $this->providerWithCredentials('client-id', 'two words');

        $this->assertSame(
            'Basic '.base64_encode('client-id:two+words'),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function a_non_ascii_secret_is_encoded(): void
    {
        $provider = $this->providerWithCredentials('client-id', 'påsswörd');

        $this->assertSame(
            'Basic '.base64_encode('client-id:'.urlencode('påsswörd')),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function a_client_id_needing_encoding_is_encoded_too(): void
    {
        $provider = $this->providerWithCredentials('client id+1', 'secret');

        $this->assertSame(
            'Basic '.base64_encode('client+id%2B1:secret'),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function an_alphanumeric_secret_is_unaffected(): void
    {
        // Confirms the change is a no-op for credentials that need no encoding.
        $provider = $this->providerWithCredentials('client-id', 'plainsecret123');

        $this->assertSame(
            'Basic '.base64_encode('client-id:plainsecret123'),
            $this->authorizationHeader($provider)
        );
    }

    #[Test]
    public function guzzles_auth_option_is_no_longer_used(): void
    {
        $provider = $this->providerWithCredentials('client-id', 'secret');

        $options = $provider->callTokenRequestOptions(['code' => 'c']);

        $this->assertArrayNotHasKey(RequestOptions::AUTH, $options);
    }

    #[Test]
    public function the_credentials_are_kept_out_of_the_form_body(): void
    {
        $provider = $this->providerWithCredentials('client-id', 'secret');

        $options = $provider->callTokenRequestOptions([
            'client_id'     => 'client-id',
            'client_secret' => 'secret',
            'code'          => 'c',
        ]);

        $this->assertArrayNotHasKey('client_id', $options[RequestOptions::FORM_PARAMS]);
        $this->assertArrayNotHasKey('client_secret', $options[RequestOptions::FORM_PARAMS]);
    }

    #[Test]
    public function the_accept_header_is_retained(): void
    {
        $provider = $this->providerWithCredentials('client-id', 'secret');

        $headers = $provider->callTokenRequestOptions(['code' => 'c'])[RequestOptions::HEADERS];

        $this->assertSame('application/json', $headers['Accept']);
    }

    #[Test]
    public function the_post_method_sends_no_authorization_header(): void
    {
        $provider = $this->oidcProvider(['token_auth_method' => 'client_secret_post']);

        $headers = $provider->callTokenRequestOptions(['code' => 'c'])[RequestOptions::HEADERS];

        $this->assertArrayNotHasKey('Authorization', $headers);
    }
}
