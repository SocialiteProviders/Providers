<?php

namespace SocialiteProviders\Tests\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Manager\OAuth2\User;
use SocialiteProviders\Microsoft\MicrosoftAvatarSize;
use SocialiteProviders\Microsoft\Provider;
use SocialiteProviders\Tests\TestCase;

class MicrosoftProviderTest extends TestCase
{
    private const AVATAR_URL = 'https://graph.microsoft.com/v1.0/me/photo/$value';

    /**
     * The requests sent by the provider, as recorded by Guzzle's history middleware.
     *
     * @var array<int, array{request: RequestInterface, options: array<string, mixed>}>
     */
    private array $history = [];

    protected function provider(): string
    {
        return Provider::class;
    }

    /**
     * @param  array<int, Response|ConnectException>  $responses
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $clientOptions
     */
    private function providerWith(array $responses, array $config = [], array $clientOptions = [], ?\Illuminate\Http\Request $request = null): Provider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        /** @var Provider $provider */
        $provider = $this->makeProvider($request);
        $provider->setConfig(new Config(static::CLIENT_ID, static::CLIENT_SECRET, static::REDIRECT_URI, $config));
        $provider->setHttpClient(new Client(['handler' => $stack] + $clientOptions));

        return $provider;
    }

    private function userResponse(): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json'));
    }

    public function test_it_returns_the_default_user_without_an_avatar(): void
    {
        $user = $this->providerWith([$this->userResponse()])->userFromToken('access-token');

        $this->assertSame(User::class, $user::class);
        $this->assertSame('microsoft-user-id', $user->getId());
        $this->assertSame('microsoft.user@example.com', $user->getEmail());
        $this->assertNull($user->getAvatar());
        $this->assertCount(1, $this->history);
    }

    public function test_it_includes_the_largest_avatar_as_a_data_url(): void
    {
        $user = $this->providerWith(
            [$this->userResponse(), new Response(200, ['Content-Type' => 'image/png'], 'png-bytes')],
            ['include_avatar' => true]
        )->userFromToken('access-token');

        $this->assertSame('data:image/png;base64,'.base64_encode('png-bytes'), $user->getAvatar());

        $request = $this->history[1]['request'];

        $this->assertSame(self::AVATAR_URL, (string) $request->getUri());
        $this->assertSame('image/*', $request->getHeaderLine('Accept'));
        $this->assertSame('Bearer access-token', $request->getHeaderLine('Authorization'));
    }

    public function test_it_includes_the_avatar_in_the_configured_size(): void
    {
        $user = $this->providerWith(
            [$this->userResponse(), new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes')],
            ['include_avatar' => true, 'include_avatar_size' => MicrosoftAvatarSize::SIZE_240]
        )->userFromToken('access-token');

        $this->assertSame('data:image/jpeg;base64,'.base64_encode('jpeg-bytes'), $user->getAvatar());
        $this->assertSame(
            'https://graph.microsoft.com/v1.0/me/photos/240x240/$value',
            (string) $this->history[1]['request']->getUri()
        );
    }

    /**
     * @return array<string, array{0: array<string, string>, 1: string}>
     */
    public static function avatarContentTypes(): array
    {
        return [
            'missing header'         => [[], 'image/jpeg'],
            'header with parameters' => [['Content-Type' => 'image/gif; charset=binary'], 'image/gif'],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    #[DataProvider('avatarContentTypes')]
    public function test_it_derives_the_mime_type_of_the_data_url_from_the_response(array $headers, string $mimeType): void
    {
        $user = $this->providerWith(
            [$this->userResponse(), new Response(200, $headers, 'bytes')],
            ['include_avatar' => true]
        )->userFromToken('access-token');

        $this->assertSame('data:'.$mimeType.';base64,'.base64_encode('bytes'), $user->getAvatar());
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
            'empty body' => [
                new Response(200, ['Content-Type' => 'image/jpeg'], ''),
            ],
        ];
    }

    #[DataProvider('failedAvatarResponses')]
    public function test_a_failed_avatar_request_does_not_break_the_sign_in(Response|ConnectException $avatarResponse): void
    {
        $user = $this->providerWith(
            [$this->userResponse(), $avatarResponse],
            ['include_avatar' => true]
        )->userFromToken('access-token');

        $this->assertSame('microsoft-user-id', $user->getId());
        $this->assertNull($user->getAvatar());
    }

    public function test_a_failed_avatar_request_is_ignored_when_http_errors_are_disabled(): void
    {
        $user = $this->providerWith(
            [$this->userResponse(), new Response(404, ['Content-Type' => 'application/json'], '{"error":{}}')],
            ['include_avatar' => true],
            ['http_errors' => false]
        )->userFromToken('access-token');

        $this->assertNull($user->getAvatar());
    }

    public function test_it_rejects_an_unknown_configured_avatar_size(): void
    {
        $provider = $this->providerWith(
            [$this->userResponse()],
            ['include_avatar' => true, 'include_avatar_size' => '100x100']
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid avatar size "100x100"');

        $provider->userFromToken('access-token');
    }

    public function test_the_avatar_request_uses_the_configured_proxy(): void
    {
        $this->providerWith(
            [$this->userResponse(), new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes')],
            ['include_avatar' => true, 'proxy' => 'http://proxy.example.com:8080']
        )->userFromToken('access-token');

        $this->assertSame('http://proxy.example.com:8080', $this->history[1]['options']['proxy']);
    }

    public function test_get_avatar_response_returns_the_image_response(): void
    {
        $response = $this->providerWith(
            [new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes')]
        )->getAvatarResponse(MicrosoftAvatarSize::SIZE_96, 'access-token');

        $this->assertNotNull($response);
        $this->assertSame('image/jpeg', $response->getHeaderLine('Content-Type'));
        $this->assertSame('jpeg-bytes', (string) $response->getBody());

        $request = $this->history[0]['request'];

        $this->assertSame('https://graph.microsoft.com/v1.0/me/photos/96x96/$value', (string) $request->getUri());
        $this->assertSame('Bearer access-token', $request->getHeaderLine('Authorization'));
    }

    public function test_get_avatar_response_returns_null_without_a_photo(): void
    {
        $response = $this->providerWith([new Response(404)])->getAvatarResponse(null, 'access-token');

        $this->assertNull($response);
    }

    public function test_get_avatar_response_reuses_the_token_of_the_loaded_user(): void
    {
        $provider = $this->providerWith(
            [
                new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"user-token"}'),
                $this->userResponse(),
                new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes'),
            ],
            [],
            [],
            $this->makeRequest(['code' => 'auth-code'])
        );

        $provider->stateless()->user();
        $response = $provider->getAvatarResponse();

        $this->assertNotNull($response);
        $this->assertSame(self::AVATAR_URL, (string) $this->history[2]['request']->getUri());
        $this->assertSame('Bearer user-token', $this->history[2]['request']->getHeaderLine('Authorization'));
    }

    public function test_get_avatar_response_requires_a_token(): void
    {
        $provider = $this->providerWith([]);

        $this->expectException(LogicException::class);

        $provider->getAvatarResponse();
    }

    public function test_get_avatar_response_rejects_an_unknown_size(): void
    {
        $provider = $this->providerWith([]);

        $this->expectException(InvalidArgumentException::class);

        $provider->getAvatarResponse('100x100', 'access-token');
    }
}
