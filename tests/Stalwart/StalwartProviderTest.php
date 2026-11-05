<?php

namespace SocialiteProviders\Tests\Stalwart;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use InvalidArgumentException;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Stalwart\Provider;
use SocialiteProviders\Tests\TestCase;

class StalwartProviderTest extends TestCase
{
    private const BASE_URL = 'https://mail.example.com';

    protected function provider(): string
    {
        return Provider::class;
    }

    private function configured(?Request $request = null, array $responses = [], array $config = []): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider($request ?? $this->makeRequestWithSession(), $responses);

        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            array_merge(['base_url' => self::BASE_URL], $config)
        ));

        return $provider;
    }

    public function test_redirect_targets_the_authorize_endpoint(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->configured($request)->stateless()->redirect()->getTargetUrl();

        $this->assertStringStartsWith(self::BASE_URL.'/oauth/authorize', $url);

        $params = $this->queryParams($url);

        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('openid profile email', $params['scope']);
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertNotEmpty($params['code_challenge']);
    }

    public function test_user_is_mapped_from_the_api_response(): void
    {
        $responses = [
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token'  => 'access-token',
                'refresh_token' => 'refresh-token',
                'expires_in'    => 3600,
                'token_type'    => 'Bearer',
            ])),
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $user = $this->configured($request, $responses)->stateless()->user();

        $this->assertSame('access-token', $user->token);
        $this->assertSame('usr_01HXYZ9876543210ABCDEF', $user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('jdoe', $user->getNickname());
        $this->assertSame('jdoe@example.com', $user->getEmail());
        $this->assertSame('https://example.com/avatar.png', $user->getAvatar());
    }

    public function test_refresh_token_uses_the_stalwart_token_endpoint(): void
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
        $this->assertSame(self::BASE_URL.'/oauth/token', (string) $request?->getUri());

        parse_str((string) $request?->getBody(), $body);
        $this->assertSame('refresh_token', $body['grant_type']);
        $this->assertSame('refresh-token', $body['refresh_token']);
        $this->assertSame(static::CLIENT_ID, $body['client_id']);
        $this->assertSame(static::CLIENT_SECRET, $body['client_secret']);
    }

    public function test_exception_thrown_when_base_url_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var Provider $provider */
        $provider = $this->makeProvider($this->makeRequestWithSession());
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            []
        ));

        $provider->stateless()->redirect();
    }
}
