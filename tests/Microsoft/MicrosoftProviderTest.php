<?php

namespace SocialiteProviders\Tests\Microsoft;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request as HttpRequest;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Http\Message\RequestInterface;
use SocialiteProviders\Manager\Config;
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
    private function providerWith(array $responses, array $config = [], array $clientOptions = [], ?HttpRequest $request = null): Provider
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        /** @var Provider $provider */
        $provider = $this->makeProvider($request);
        $provider->setConfig(new Config(static::CLIENT_ID, static::CLIENT_SECRET, static::REDIRECT_URI, $config));
        $provider->setHttpClient(new Client(['handler' => $stack] + $clientOptions));

        return $provider;
    }

    public function test_get_avatar_response_returns_the_largest_photo(): void
    {
        $response = $this->providerWith(
            [new Response(200, ['Content-Type' => 'image/png'], 'png-bytes')]
        )->getAvatarResponse(null, 'access-token');

        $this->assertNotNull($response);
        $this->assertSame('image/png', $response->getHeaderLine('Content-Type'));
        $this->assertSame('png-bytes', (string) $response->getBody());

        $request = $this->history[0]['request'];

        $this->assertSame(self::AVATAR_URL, (string) $request->getUri());
        $this->assertSame('image/*', $request->getHeaderLine('Accept'));
        $this->assertSame('Bearer access-token', $request->getHeaderLine('Authorization'));
    }

    public function test_get_avatar_response_returns_the_photo_in_the_given_size(): void
    {
        $response = $this->providerWith(
            [new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes')]
        )->getAvatarResponse(MicrosoftAvatarSize::SIZE_96, 'access-token');

        $this->assertNotNull($response);
        $this->assertSame(
            'https://graph.microsoft.com/v1.0/me/photos/96x96/$value',
            (string) $this->history[0]['request']->getUri()
        );
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
    public function test_get_avatar_response_returns_null_when_the_request_fails(Response|ConnectException $avatarResponse): void
    {
        $response = $this->providerWith([$avatarResponse])->getAvatarResponse(null, 'access-token');

        $this->assertNull($response);
    }

    public function test_get_avatar_response_returns_null_when_http_errors_are_disabled(): void
    {
        $response = $this->providerWith(
            [new Response(404, ['Content-Type' => 'application/json'], '{"error":{}}')],
            [],
            ['http_errors' => false]
        )->getAvatarResponse(null, 'access-token');

        $this->assertNull($response);
    }

    public function test_get_avatar_response_uses_the_configured_proxy(): void
    {
        $this->providerWith(
            [new Response(200, ['Content-Type' => 'image/jpeg'], 'jpeg-bytes')],
            ['proxy' => 'http://proxy.example.com:8080']
        )->getAvatarResponse(null, 'access-token');

        $this->assertSame('http://proxy.example.com:8080', $this->history[0]['options']['proxy']);
    }

    public function test_get_avatar_response_reuses_the_token_of_the_loaded_user(): void
    {
        $provider = $this->providerWith(
            [
                new Response(200, ['Content-Type' => 'application/json'], '{"access_token":"user-token"}'),
                new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
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
        $this->expectExceptionMessage('Invalid avatar size "100x100"');

        $provider->getAvatarResponse('100x100', 'access-token');
    }
}
