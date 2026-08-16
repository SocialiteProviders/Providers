<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class RefreshTokenTest extends TestCase
{
    use InteractsWithOidc;

    public function test_refresh_token_posts_the_grant_and_returns_the_parsed_body(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
            $this->jsonResponse([
                'access_token'  => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in'    => 3600,
            ]),
        ]);

        $result = $provider->refreshToken('old-refresh-token');

        $this->assertSame('new-access-token', $result['access_token']);

        parse_str((string) $this->lastRequestTo('/token')->getBody(), $body);

        $this->assertSame('refresh_token', $body['grant_type']);
        $this->assertSame('old-refresh-token', $body['refresh_token']);
        $this->assertSame(static::$opClientId, $body['client_id']);
    }

    public function test_refresh_token_respects_the_token_auth_method(): void
    {
        $provider = $this->makeProvider(
            ['token_auth_method' => 'client_secret_basic'],
            [
                $this->jsonResponse($this->discoveryDocument()),
                $this->jsonResponse(['access_token' => 'new-access-token']),
            ],
        );

        $provider->refreshToken('old-refresh-token');

        $request = $this->lastRequestTo('/token');
        parse_str((string) $request->getBody(), $body);

        $this->assertTrue($request->hasHeader('Authorization'));
        $this->assertArrayNotHasKey('client_secret', $body);
    }
}
