<?php

namespace SocialiteProviders\Tests\Hytale;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SocialiteProviders\Hytale\Provider;
use SocialiteProviders\Tests\TestCase;

class HytaleProviderTest extends TestCase
{
    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * See getCodeChallenge() in laravel/socialite's AbstractProvider.
     */
    private static function s256(string $verifier): string
    {
        return rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
    }

    public function test_redirect_targets_the_authorize_endpoint(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->makeProvider($request)->stateless()->redirect()->getTargetUrl();

        $this->assertSame('connect.accounts.hytale.com', parse_url($url, PHP_URL_HOST));
        $this->assertSame('/oauth2/auth', parse_url($url, PHP_URL_PATH));

        $params = $this->queryParams($url);

        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('openid', $params['scope']);
    }

    public function test_extra_scopes_are_added_to_the_redirect(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->makeProvider($request)
            ->stateless()
            ->scopes(['hytale:profile', 'account:shared_source'])
            ->redirect()
            ->getTargetUrl();

        $params = $this->queryParams($url);

        $this->assertSame('openid hytale:profile account:shared_source', $params['scope']);
    }

    public function test_pkce_challenge_is_derived_from_the_session_verifier(): void
    {
        $request = $this->makeRequestWithSession();

        $params = $this->queryParams($this->makeProvider($request)->stateless()->redirect()->getTargetUrl());

        $verifier = $request->session()->get('code_verifier');

        $this->assertNotNull($verifier, 'code_verifier was not stored in the session');
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertSame($this->s256($verifier), $params['code_challenge']);
    }

    public function test_state_is_stored_in_the_session_when_not_stateless(): void
    {
        $request = $this->makeRequestWithSession();

        $params = $this->queryParams($this->makeProvider($request)->redirect()->getTargetUrl());

        $this->assertSame($request->session()->get('state'), $params['state']);
    }

    public function test_token_exchange_authenticates_with_http_basic(): void
    {
        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'access-token',
            ])),
        ]);

        $provider = new Provider(
            $this->makeRequestWithSession([], ['code_verifier' => 'the-code-verifier']),
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI
        );
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]));

        $provider->getAccessTokenResponse('the-code');

        $request = $handler->getLastRequest();

        $this->assertSame(
            'Basic '.base64_encode(static::CLIENT_ID.':'.static::CLIENT_SECRET),
            $request->getHeaderLine('Authorization')
        );

        parse_str((string) $request->getBody(), $body);

        $this->assertArrayNotHasKey('client_id', $body);
        $this->assertArrayNotHasKey('client_secret', $body);
        $this->assertSame('authorization_code', $body['grant_type']);
        $this->assertSame('the-code', $body['code']);
        $this->assertSame('the-code-verifier', $body['code_verifier']);
    }

    public function test_public_client_sends_client_id_in_the_body(): void
    {
        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'access-token',
            ])),
        ]);

        $provider = new Provider(
            $this->makeRequestWithSession([], ['code_verifier' => 'the-code-verifier']),
            static::CLIENT_ID,
            '',
            static::REDIRECT_URI
        );
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]));

        $provider->getAccessTokenResponse('the-code');

        $request = $handler->getLastRequest();

        $this->assertSame('', $request->getHeaderLine('Authorization'));

        parse_str((string) $request->getBody(), $body);

        $this->assertSame(static::CLIENT_ID, $body['client_id']);
        $this->assertArrayNotHasKey('client_secret', $body);
        $this->assertSame('the-code-verifier', $body['code_verifier']);
    }

    public function test_user_is_mapped_from_the_api_response(): void
    {
        $handler = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'access-token',
            ])),
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ]);

        $provider = new Provider(
            $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']),
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI
        );
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($handler)]));

        $user = $provider->stateless()->user();

        $userinfo = $handler->getLastRequest();

        $this->assertSame('https://connect.accounts.hytale.com/userinfo', (string) $userinfo->getUri());
        $this->assertSame('Bearer access-token', $userinfo->getHeaderLine('Authorization'));

        $fixture = $this->fixtureJson('user.json');

        $this->assertSame('access-token', $user->token);
        $this->assertSame($fixture['sub'], $user->getId());
        $this->assertSame($fixture['profile']['username'], $user->getNickname());
        $this->assertSame($fixture['profile']['uuid'], $user->uuid);
        $this->assertNull($user->shared_source);
        $this->assertNull($user->game_ownership);
        $this->assertNull($user->parental_managed);
        $this->assertNull($user->getEmail());
        $this->assertNull($user->getAvatar());
    }

    public function test_optional_account_claims_are_mapped_when_granted(): void
    {
        $responses = [
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'access-token',
            ])),
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user-full.json')),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $user = $this->makeProvider($request, $responses)->stateless()->user();

        $this->assertTrue($user->shared_source);
        $this->assertTrue($user->game_ownership);
        $this->assertFalse($user->parental_managed);
    }
}
