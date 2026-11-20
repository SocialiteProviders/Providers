<?php

namespace SocialiteProviders\Tests\Tidal;

use GuzzleHttp\Psr7\Response;
use RuntimeException;
use SocialiteProviders\Tests\TestCase;
use SocialiteProviders\Tidal\Provider;

class TidalProviderTest extends TestCase
{
    protected function provider(): string
    {
        return Provider::class;
    }

    public function test_redirect_targets_the_authorize_endpoint(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->makeProvider($request)->stateless()->redirect()->getTargetUrl();

        $this->assertStringStartsWith('https://login.tidal.com/authorize?', $url);

        $params = $this->queryParams($url);

        $this->assertSame(static::CLIENT_ID, $params['client_id']);
        $this->assertSame(static::REDIRECT_URI, $params['redirect_uri']);
        $this->assertSame('code', $params['response_type']);
        $this->assertSame('user.read', $params['scope']);
        $this->assertSame('S256', $params['code_challenge_method']);
        $this->assertNotEmpty($params['code_challenge']);
    }

    public function test_user_is_mapped_from_the_api_response(): void
    {
        $responses = [
            new Response(200, ['Content-Type' => 'application/json'], (string) json_encode([
                'access_token' => 'access-token',
            ])),
            new Response(200, ['Content-Type' => 'application/vnd.api+json'], $this->fixture('user.json')),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $user = $this->makeProvider($request, $responses)->stateless()->user();

        $this->assertSame('access-token', $user->token);
        $this->assertSame('12345', $user->getId());
        $this->assertSame('j@doe.com', $user->getNickname());
        $this->assertSame('j@doe.com', $user->getEmail());
        $this->assertNull($user->getName());
        $this->assertNull($user->getAvatar());
    }

    /**
     * Live accounts often omit firstName/lastName, but they are joined when present.
     */
    public function test_user_name_is_joined_from_first_and_last_name(): void
    {
        $document = $this->fixtureJson('user.json');
        $document['data']['attributes']['firstName'] = 'J.';
        $document['data']['attributes']['lastName'] = 'Doe';

        $responses = [
            new Response(200, [], (string) json_encode(['access_token' => 'access-token'])),
            new Response(200, [], (string) json_encode($document)),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $user = $this->makeProvider($request, $responses)->stateless()->user();

        $this->assertSame('J. Doe', $user->getName());
    }

    public function test_user_read_scope_is_kept_when_scopes_are_replaced(): void
    {
        $request = $this->makeRequestWithSession();

        $url = $this->makeProvider($request)->stateless()->setScopes(['playlists.read'])->redirect()->getTargetUrl();

        $this->assertSame('user.read playlists.read', $this->queryParams($url)['scope']);
    }

    public function test_missing_user_data_throws(): void
    {
        $responses = [
            new Response(200, [], (string) json_encode(['access_token' => 'access-token'])),
            new Response(200, [], (string) json_encode(['errors' => [[
                'status' => '403',
                'code'   => 'FORBIDDEN',
                'detail' => 'Missing scope user.read',
            ]]])),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TIDAL user response is missing data: 403 FORBIDDEN Missing scope user.read');

        $this->makeProvider($request, $responses)->stateless()->user();
    }

    public function test_unexpected_user_response_throws(): void
    {
        $responses = [
            new Response(200, [], (string) json_encode(['access_token' => 'access-token'])),
            new Response(200, [], 'not json'),
        ];

        $request = $this->makeRequestWithSession(['code' => 'code', 'state' => 'state']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('TIDAL user response is missing data.');

        $this->makeProvider($request, $responses)->stateless()->user();
    }
}
