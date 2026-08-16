<?php

namespace SocialiteProviders\Tests\Frappe;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use SocialiteProviders\Frappe\Provider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase;

class FrappeProviderTest extends TestCase
{
    private const BASE_URL = 'https://erp.example.com';

    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * @param  array<int, Response>  $responses
     */
    private function configured(?Request $request = null, array $responses = [], array $fields = []): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request, $responses);

        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            [
                'base_url' => self::BASE_URL,
                'fields'   => $fields,
            ]
        ));

        return $provider;
    }

    public function test_redirect_targets_the_frappe_authorize_endpoint(): void
    {
        $url = $this->configured()->stateless()->redirect()->getTargetUrl();

        $this->assertStringStartsWith(
            self::BASE_URL.'/api/method/frappe.integrations.oauth2.authorize',
            $url
        );

        $params = $this->queryParams($url);

        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('openid', $params['scope']);
    }

    public function test_user_maps_the_openid_profile(): void
    {
        $provider = $this->configured(
            $this->makeRequest(['code' => 'auth-code', 'state' => 'state']),
            [
                new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'access_token'  => 'access-token',
                    'refresh_token' => 'refresh-token',
                    'expires_in'    => 3600,
                    'token_type'    => 'Bearer',
                ])),
                new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
            ]
        );

        $user = $provider->stateless()->user();

        $this->assertSame('e1b9c2d3f4a5b6c7', $user->getId());
        $this->assertSame('Ada Lovelace', $user->getName());
        $this->assertSame('ada@example.com', $user->getEmail());
        $this->assertSame(self::BASE_URL.'/files/ada.png', $user->getAvatar());
        $this->assertSame('access-token', $user->token);
        $this->assertSame(['System Manager', 'Sales User'], $user->getRaw()['roles']);
    }

    public function test_configured_fields_are_requested_and_added_to_the_raw_user(): void
    {
        $fields = ['custom_department', 'custom_employee_number'];
        $redirect = $this->configured(null, [], $fields)->stateless()->redirect()->getTargetUrl();

        $this->assertSame('openid all', $this->queryParams($redirect)['scope']);

        $provider = $this->configured(
            $this->makeRequest(['code' => 'auth-code', 'state' => 'state']),
            [
                new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'access_token' => 'access-token',
                ])),
                new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
                new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'data' => [[
                        'custom_department'      => 'Research',
                        'custom_employee_number' => 'EMP-001',
                    ]],
                ])),
            ],
            $fields
        );

        $raw = $provider->stateless()->user()->getRaw();

        $this->assertSame('Research', $raw['custom_department']);
        $this->assertSame('EMP-001', $raw['custom_employee_number']);
    }

    public function test_id_falls_back_to_email_when_sub_is_missing(): void
    {
        $provider = $this->configured(
            $this->makeRequest(['code' => 'auth-code', 'state' => 'state']),
            [
                new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'access_token' => 'access-token',
                ])),
                new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                    'name'  => 'No Sub',
                    'email' => 'nosub@example.com',
                ])),
            ]
        );

        $this->assertSame('nosub@example.com', $provider->stateless()->user()->getId());
    }

    public function test_user_can_be_fetched_from_an_access_token(): void
    {
        $provider = $this->configured(null, [
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ]);

        $user = $provider->userFromToken('access-token');

        $this->assertSame('e1b9c2d3f4a5b6c7', $user->getId());
        $this->assertSame('access-token', $user->token);
    }

    public function test_refresh_token_uses_the_frappe_token_endpoint(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'refreshed-access-token',
            ])),
        ]);
        $provider = $this->configured();
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $response = $provider->refreshToken('refresh-token');
        $request = $mock->getLastRequest();

        $this->assertSame('refreshed-access-token', $response['access_token']);
        $this->assertSame(self::BASE_URL.'/api/method/frappe.integrations.oauth2.get_token', (string) $request?->getUri());

        parse_str((string) $request?->getBody(), $body);
        $this->assertSame('refresh_token', $body['grant_type']);
        $this->assertSame('refresh-token', $body['refresh_token']);
    }

    public function test_logout_url_and_token_revocation_use_frappe_endpoints(): void
    {
        $mock = new MockHandler([new Response(200)]);
        $provider = $this->configured();
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $this->assertSame(self::BASE_URL.'/api/method/logout', $provider->getLogoutUrl());
        $this->assertSame(200, $provider->revokeToken('access-token')->getStatusCode());

        $request = $mock->getLastRequest();
        $this->assertSame('POST', $request?->getMethod());
        $this->assertSame(
            self::BASE_URL.'/api/method/frappe.integrations.oauth2.revoke_token',
            (string) $request?->getUri()
        );

        parse_str((string) $request?->getBody(), $body);
        $this->assertSame('access-token', $body['token']);
    }
}
