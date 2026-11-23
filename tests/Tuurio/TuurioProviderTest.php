<?php

namespace SocialiteProviders\Tests\Tuurio;

use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Laravel\Socialite\Two\InvalidStateException;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase;
use SocialiteProviders\Tuurio\Provider;

class TuurioProviderTest extends TestCase
{
    private const BASE_URL = 'https://acme.id.tuurio.com';

    /** @var array<int, array<string, mixed>> */
    private array $history = [];

    protected function provider(): string
    {
        return Provider::class;
    }

    private static function s256(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    private function configured(?Request $request = null, array $responses = [], ?string $baseUrl = self::BASE_URL): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request ?? $this->makeRequestWithSession());

        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['base_url' => $baseUrl]
        ));

        $client = $this->makeHttpClient($responses);
        $client->getConfig('handler')->push(Middleware::history($this->history));
        $provider->setHttpClient($client);

        return $provider;
    }

    public function test_redirect_targets_the_tenant_authorize_endpoint(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->configured($request)->redirect()->getTargetUrl();
        $params = $this->queryParams($url);

        $this->assertStringStartsWith(self::BASE_URL.'/oauth2/authorize?', $url);
        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('openid profile email', $params['scope']);
        $this->assertSame($request->session()->get('state'), $params['state']);
    }

    public function test_redirect_always_sends_an_s256_challenge_derived_from_the_stored_verifier(): void
    {
        $request = $this->makeRequestWithSession();

        $params = $this->queryParams($this->configured($request)->redirect()->getTargetUrl());

        $verifier = $request->session()->get('code_verifier');
        $this->assertNotNull($verifier, 'code_verifier was not stored in the session');
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertSame(self::s256($verifier), $params['code_challenge']);
    }

    public function test_a_trailing_slash_on_base_url_is_ignored(): void
    {
        $url = $this->configured(null, [], self::BASE_URL.'/')->redirect()->getTargetUrl();

        $this->assertStringStartsWith(self::BASE_URL.'/oauth2/authorize?', $url);
    }

    public function test_missing_base_url_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing base_url');

        $this->configured(null, [], null)->redirect();
    }

    public function test_plain_http_base_url_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('base_url must use https');

        $this->configured(null, [], 'http://acme.id.tuurio.com')->redirect();
    }

    public function test_callback_with_a_mismatching_state_is_rejected_before_any_request(): void
    {
        $request = $this->makeRequestWithSession(
            ['code' => 'code', 'state' => 'forged'],
            ['state' => 'state', 'code_verifier' => 'verifier']
        );

        try {
            $this->configured($request, [new Response(200)])->user();
            $this->fail('A mismatching state was accepted.');
        } catch (InvalidStateException) {
            $this->assertCount(0, $this->history);
        }
    }

    public function test_callback_without_a_state_is_rejected_before_any_request(): void
    {
        $request = $this->makeRequestWithSession(
            ['code' => 'code'],
            ['state' => 'state', 'code_verifier' => 'verifier']
        );

        try {
            $this->configured($request, [new Response(200)])->user();
            $this->fail('A callback without state was accepted.');
        } catch (InvalidStateException) {
            $this->assertCount(0, $this->history);
        }
    }

    public function test_user_is_fetched_from_the_tenant_and_mapped(): void
    {
        $responses = [
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token'  => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in'    => 900,
            ])),
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ];

        $request = $this->makeRequestWithSession(
            ['code' => 'code', 'state' => 'state'],
            ['state' => 'state', 'code_verifier' => 'verifier']
        );

        $user = $this->configured($request, $responses)->user();

        $this->assertCount(2, $this->history);

        $tokenRequest = $this->history[0]['request'];
        parse_str((string) $tokenRequest->getBody(), $tokenBody);
        $this->assertSame(self::BASE_URL.'/oauth2/token', (string) $tokenRequest->getUri());
        $this->assertSame('authorization_code', $tokenBody['grant_type']);
        $this->assertSame('code', $tokenBody['code']);
        $this->assertSame('verifier', $tokenBody['code_verifier']);

        $userinfoRequest = $this->history[1]['request'];
        $this->assertSame(self::BASE_URL.'/userinfo', (string) $userinfoRequest->getUri());
        $this->assertSame('Bearer access-token', $userinfoRequest->getHeaderLine('Authorization'));

        $this->assertSame('access-token', $user->token);
        $this->assertSame('refresh-token', $user->refreshToken);
        $this->assertSame('5f0c1d7e-2b6a-4c7e-9a31-8d2f4e6b1a90', $user->getId());
        $this->assertSame('jane@example.com', $user->getNickname());
        $this->assertSame('Jane Doe', $user->getName());
        $this->assertSame('jane@example.com', $user->getEmail());
        $this->assertTrue($user->email_verified);
        $this->assertSame('de', $user->locale);
    }
}
