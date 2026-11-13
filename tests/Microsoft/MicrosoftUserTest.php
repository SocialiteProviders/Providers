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
use SocialiteProviders\Microsoft\MicrosoftUser;
use SocialiteProviders\Microsoft\Provider;
use SocialiteProviders\Tests\TestCase;

class MicrosoftUserTest extends TestCase
{
    private const AVATAR_URL = 'https://graph.microsoft.com/v1.0/me/photo/$value';

    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function userFromProvider(MockHandler $mock, array $config = []): MicrosoftUser
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(static::CLIENT_ID, static::CLIENT_SECRET, static::REDIRECT_URI, $config));
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        return $provider->userFromToken('access-token');
    }

    private function userResponse(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json'));
    }

    public function test_avatar_is_fetched_with_the_http_client_of_the_provider(): void
    {
        $mock = new MockHandler([
            $this->userResponse(),
            new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
        ]);

        $avatar = $this->userFromProvider($mock)->getAvatar();

        $this->assertSame('data:image/jpeg;base64,'.base64_encode('jpeg-bytes'), (string) $avatar);
        $this->assertSame(self::AVATAR_URL, (string) $mock->getLastRequest()?->getUri());
        $this->assertSame('Bearer access-token', $mock->getLastRequest()?->getHeaderLine('Authorization'));
    }

    public function test_avatar_request_honours_the_guzzle_options_of_the_provider(): void
    {
        $mock = new MockHandler([
            $this->userResponse(),
            new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
        ]);

        // Socialite passes the "guzzle" key of config/services.php to the constructor.
        $provider = new Provider(
            $this->makeRequest(),
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['timeout' => 5, 'handler' => HandlerStack::create($mock)]
        );
        $provider->setConfig(new Config(static::CLIENT_ID, static::CLIENT_SECRET, static::REDIRECT_URI));

        $provider->userFromToken('access-token')->getAvatar();

        $this->assertSame(self::AVATAR_URL, (string) $mock->getLastRequest()?->getUri());
        $this->assertSame(5, $mock->getLastOptions()['timeout']);
    }

    public function test_avatar_request_uses_the_configured_proxy(): void
    {
        $mock = new MockHandler([
            $this->userResponse(),
            new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
        ]);

        $this->userFromProvider($mock, ['proxy' => 'http://proxy.example.test:8080'])->getAvatar();

        $this->assertSame(self::AVATAR_URL, (string) $mock->getLastRequest()?->getUri());
        $this->assertSame('http://proxy.example.test:8080', $mock->getLastOptions()['proxy']);
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
    public function test_failed_avatar_request_returns_null(Response|ConnectException $avatarResponse): void
    {
        $mock = new MockHandler([
            $this->userResponse(),
            $avatarResponse,
        ]);

        $avatar = $this->userFromProvider($mock)->getAvatar();

        $this->assertNull($avatar);
        $this->assertSame(0, $mock->count());
    }

    public function test_user_can_be_serialized_without_its_http_client(): void
    {
        $user = $this->userFromProvider(new MockHandler([$this->userResponse()]), [
            'proxy' => 'http://proxy.example.test:8080',
        ]);

        $restored = unserialize(serialize($user));

        $this->assertInstanceOf(MicrosoftUser::class, $restored);
        $this->assertSame('microsoft-user-id', $restored->getId());
        $this->assertSame('microsoft.user@example.com', $restored->getEmail());
        $this->assertSame('access-token', $restored->token);
    }
}
