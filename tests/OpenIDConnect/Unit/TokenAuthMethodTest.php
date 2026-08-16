<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class TokenAuthMethodTest extends TestCase
{
    use InteractsWithOidc;

    private function tokenRequestBody(): array
    {
        parse_str((string) $this->lastRequestTo('/token')->getBody(), $body);

        return $body;
    }

    public function test_client_secret_post_is_the_default(): void
    {
        $provider = $this->makeProvider([], $this->happyPathResponses());
        $provider->user();

        $body = $this->tokenRequestBody();

        $this->assertSame(static::$opClientId, $body['client_id']);
        $this->assertSame(static::$opClientSecret, $body['client_secret']);
        $this->assertSame('authorization_code', $body['grant_type']);
        $this->assertSame('the-auth-code', $body['code']);
        $this->assertFalse($this->lastRequestTo('/token')->hasHeader('Authorization'));
    }

    public function test_client_secret_basic_moves_credentials_to_the_authorization_header(): void
    {
        $provider = $this->makeProvider(
            ['token_auth_method' => 'client_secret_basic'],
            $this->happyPathResponses(),
        );
        $provider->user();

        $request = $this->lastRequestTo('/token');
        $body = $this->tokenRequestBody();

        $expected = 'Basic '.base64_encode(urlencode(static::$opClientId).':'.urlencode(static::$opClientSecret));
        $this->assertSame($expected, $request->getHeaderLine('Authorization'));
        $this->assertArrayNotHasKey('client_id', $body);
        $this->assertArrayNotHasKey('client_secret', $body);
    }

    public function test_basic_credentials_are_form_urlencoded_before_base64(): void
    {
        // RFC 6749 2.3.1. A secret with +, / and = must round-trip; base64ing
        // the raw pair would not.
        static::$opClientSecret = 'se+cret/=:with space';

        try {
            $provider = $this->makeProvider(
                ['token_auth_method' => 'client_secret_basic'],
                $this->happyPathResponses(),
            );
            $provider->user();

            $header = $this->lastRequestTo('/token')->getHeaderLine('Authorization');
            $decoded = base64_decode(substr($header, 6));

            [$id, $secret] = explode(':', $decoded, 2);

            $this->assertSame(static::$opClientId, urldecode($id));
            $this->assertSame('se+cret/=:with space', urldecode($secret));
        } finally {
            static::$opClientSecret = 'client-secret';
        }
    }

    public function test_discovery_advertising_basic_selects_it_automatically(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument([
                'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            ])),
            $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
            $this->jsonResponse($this->jwksDocument()),
        ]);
        $provider->user();

        $this->assertTrue($this->lastRequestTo('/token')->hasHeader('Authorization'));
    }

    public function test_explicit_config_beats_the_discovery_document(): void
    {
        $provider = $this->makeProvider(
            ['token_auth_method' => 'client_secret_post'],
            [
                $this->jsonResponse($this->discoveryDocument([
                    'token_endpoint_auth_methods_supported' => ['client_secret_basic'],
                ])),
                $this->tokenEndpointResponse($this->encodeToken($this->idTokenClaims())),
                $this->jsonResponse($this->jwksDocument()),
            ],
        );
        $provider->user();

        $this->assertFalse($this->lastRequestTo('/token')->hasHeader('Authorization'));
    }
}
