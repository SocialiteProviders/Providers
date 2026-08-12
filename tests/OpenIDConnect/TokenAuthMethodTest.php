<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;

class TokenAuthMethodTest extends TestCase
{
    #[Test]
    public function an_explicitly_configured_method_wins(): void
    {
        $provider = $this->oidcProvider(
            ['token_auth_method' => 'client_secret_post'],
            discovery: $this->discoveryDocument([
                'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
            ])
        );

        $this->assertSame('client_secret_post', $provider->callResolveTokenAuthMethod());
    }

    #[Test]
    public function basic_is_preferred_when_the_op_advertises_it(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'token_endpoint_auth_methods_supported' => ['client_secret_post', 'client_secret_basic'],
        ]));

        $this->assertSame('client_secret_basic', $provider->callResolveTokenAuthMethod());
    }

    #[Test]
    public function post_is_used_when_basic_is_not_advertised(): void
    {
        $provider = $this->oidcProvider(discovery: $this->discoveryDocument([
            'token_endpoint_auth_methods_supported' => ['client_secret_post'],
        ]));

        $this->assertSame('client_secret_post', $provider->callResolveTokenAuthMethod());
    }

    #[Test]
    public function post_is_the_fallback_when_discovery_says_nothing(): void
    {
        $this->assertSame('client_secret_post', $this->oidcProvider()->callResolveTokenAuthMethod());
    }

    #[Test]
    public function basic_moves_the_credentials_out_of_the_form_body(): void
    {
        $provider = $this->oidcProvider(['token_auth_method' => 'client_secret_basic']);

        $options = $provider->callTokenRequestOptions([
            'client_id'     => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'code'          => 'the-code',
        ]);

        // Sent as a hand-built header rather than Guzzle's `auth` option; the
        // encoding itself is covered by ClientSecretBasicTest.
        $this->assertSame(
            'Basic '.base64_encode(urlencode(self::CLIENT_ID).':'.urlencode(self::CLIENT_SECRET)),
            $options[RequestOptions::HEADERS]['Authorization']
        );
        $this->assertArrayNotHasKey('client_id', $options[RequestOptions::FORM_PARAMS]);
        $this->assertArrayNotHasKey('client_secret', $options[RequestOptions::FORM_PARAMS]);
        $this->assertSame('the-code', $options[RequestOptions::FORM_PARAMS]['code']);
    }

    #[Test]
    public function post_keeps_the_credentials_in_the_form_body(): void
    {
        $provider = $this->oidcProvider(['token_auth_method' => 'client_secret_post']);

        $options = $provider->callTokenRequestOptions([
            'client_id'     => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
            'code'          => 'the-code',
        ]);

        $this->assertArrayNotHasKey(RequestOptions::AUTH, $options);
        $this->assertSame(self::CLIENT_ID, $options[RequestOptions::FORM_PARAMS]['client_id']);
        $this->assertSame(self::CLIENT_SECRET, $options[RequestOptions::FORM_PARAMS]['client_secret']);
    }
}
