<?php

namespace SocialiteProviders\Tests\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Microsoft\Provider;
use SocialiteProviders\Tests\TestCase;

class MicrosoftProviderTest extends TestCase
{
    private const AVATAR_URL = 'https://graph.microsoft.com/v1.0/me/photo/$value';

    protected function provider(): string
    {
        return Provider::class;
    }

    private function providerWithAvatar(MockHandler $mock): Provider
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['include_avatar' => true]
        ));
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        return $provider;
    }

    public function test_avatar_is_included_when_requested(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
            new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
        ]);

        $user = $this->providerWithAvatar($mock)->userFromToken('access-token');

        $this->assertSame('microsoft-user-id', $user->getId());
        $this->assertSame(base64_encode('jpeg-bytes'), $user->user['avatar']);
    }

    /**
     * @return array<string, array{0: Response|ConnectException}>
     */
    public static function failedAvatarResponses(): array
    {
        return [
            'no photo (404)' => [
                new Response(404),
            ],
            'graph outage (502)' => [
                new Response(502),
            ],
            'network error' => [
                new ConnectException('Connection timed out', new Request('GET', self::AVATAR_URL)),
            ],
        ];
    }

    #[DataProvider('failedAvatarResponses')]
    public function test_failed_avatar_request_does_not_break_the_sign_in(Response|ConnectException $avatarResponse): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
            $avatarResponse,
        ]);

        $user = $this->providerWithAvatar($mock)->userFromToken('access-token');

        $this->assertSame('microsoft-user-id', $user->getId());
        $this->assertSame('microsoft.user@example.com', $user->getEmail());
        $this->assertNull($user->user['avatar']);
        $this->assertSame(0, $mock->count());
    }
}
