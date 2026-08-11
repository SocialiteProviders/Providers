<?php

namespace SocialiteProviders\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase as BaseTestCase;
use SocialiteProviders\Manager\OAuth2\AbstractProvider;

abstract class TestCase extends BaseTestCase
{
    protected const CLIENT_ID = 'client-id';

    protected const CLIENT_SECRET = 'client-secret';

    protected const REDIRECT_URI = 'https://example.com/auth/callback';

    /**
     * The provider class under test.
     */
    abstract protected function provider(): string;

    /**
     * Build the provider, optionally with a request and queued HTTP responses.
     *
     * @param  array<int, Response>  $responses
     */
    protected function makeProvider(?Request $request = null, array $responses = []): AbstractProvider
    {
        $class = $this->provider();

        $provider = new $class(
            $request ?? $this->makeRequest(),
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI
        );

        if ($responses !== []) {
            $provider->setHttpClient($this->makeHttpClient($responses));
        }

        return $provider;
    }

    /**
     * @param  array<string, mixed>  $query
     */
    protected function makeRequest(array $query = [], ?string $uri = null): Request
    {
        return Request::create($uri ?? static::REDIRECT_URI, 'GET', $query);
    }

    /**
     * A Guzzle client that replays the given responses instead of hitting the network.
     *
     * @param  array<int, Response>  $responses
     */
    protected function makeHttpClient(array $responses): Client
    {
        return new Client([
            'handler' => HandlerStack::create(new MockHandler($responses)),
        ]);
    }

    /**
     * Read a fixture from the provider's own tests/<Provider>/fixtures directory.
     */
    protected function fixture(string $name): string
    {
        $path = $this->fixturePath($name);

        if (! is_file($path)) {
            $this->fail("Missing fixture: {$path}");
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            $this->fail("Unreadable fixture: {$path}");
        }

        return $contents;
    }

    /**
     * @return array<mixed>
     */
    protected function fixtureJson(string $name): array
    {
        return json_decode($this->fixture($name), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function fixturePath(string $name): string
    {
        return dirname((new \ReflectionClass($this))->getFileName()).'/fixtures/'.$name;
    }

    /**
     * Parse a URL's query string into an array.
     *
     * @return array<string, string>
     */
    protected function queryParams(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $params);

        return $params;
    }
}
