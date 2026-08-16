<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use ReflectionMethod;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class DiscoveryTest extends TestCase
{
    use InteractsWithOidc;

    private function openIdConfigOf(Provider $provider): array
    {
        return (new ReflectionMethod($provider, 'getOpenIdConfig'))->invoke($provider);
    }

    public function test_discovery_document_is_fetched_from_well_known_url(): void
    {
        $provider = $this->makeProvider([], [
            $this->jsonResponse($this->discoveryDocument()),
        ]);

        $config = $this->openIdConfigOf($provider);

        $this->assertSame(static::$opBaseUrl.'/token', $config['token_endpoint']);
        $this->assertSame(['GET /.well-known/openid-configuration'], $this->requestedPaths());
    }

    public function test_all_endpoints_are_derived_from_the_discovery_document(): void
    {
        $doc = $this->discoveryDocument([
            'authorization_endpoint' => static::$opBaseUrl.'/custom/auth',
            'token_endpoint'         => static::$opBaseUrl.'/custom/token',
            'userinfo_endpoint'      => static::$opBaseUrl.'/custom/userinfo',
            'jwks_uri'               => static::$opBaseUrl.'/custom/jwks',
        ]);

        $provider = $this->makeProvider([], [$this->jsonResponse($doc)]);

        $config = $this->openIdConfigOf($provider);

        $this->assertSame(static::$opBaseUrl.'/custom/auth', $config['authorization_endpoint']);
        $this->assertSame(static::$opBaseUrl.'/custom/token', $config['token_endpoint']);
        $this->assertSame(static::$opBaseUrl.'/custom/userinfo', $config['userinfo_endpoint']);
        $this->assertSame(static::$opBaseUrl.'/custom/jwks', $config['jwks_uri']);
    }

    public function test_discovery_document_is_cached_across_provider_instances(): void
    {
        $first = $this->makeProvider([], [$this->jsonResponse($this->discoveryDocument())]);
        $this->openIdConfigOf($first);

        // No queued responses: any HTTP call would blow up the MockHandler.
        $second = $this->makeProvider([], []);

        $this->assertSame(
            static::$opBaseUrl.'/token',
            $this->openIdConfigOf($second)['token_endpoint']
        );
        $this->assertSame([], $this->requestedPaths());
    }

    public function test_discovery_document_is_cached_under_a_url_derived_key(): void
    {
        $provider = $this->makeProvider([], [$this->jsonResponse($this->discoveryDocument())]);
        $this->openIdConfigOf($provider);

        $key = 'openidconnect_discovery_'.md5(static::$opBaseUrl.'/.well-known/openid-configuration');

        $this->assertTrue(Cache::has($key));
    }

    public function test_a_cache_ttl_of_zero_disables_caching(): void
    {
        $provider = $this->makeProvider(['cache_ttl' => 0], [
            $this->jsonResponse($this->discoveryDocument()),
        ]);
        $this->openIdConfigOf($provider);

        $key = 'openidconnect_discovery_'.md5(static::$opBaseUrl.'/.well-known/openid-configuration');

        $this->assertFalse(Cache::has($key));
    }

    public function test_an_empty_cache_ttl_falls_back_to_the_default(): void
    {
        $provider = $this->makeProvider(['cache_ttl' => ''], [
            $this->jsonResponse($this->discoveryDocument()),
        ]);
        $this->openIdConfigOf($provider);

        $key = 'openidconnect_discovery_'.md5(static::$opBaseUrl.'/.well-known/openid-configuration');

        $this->assertTrue(Cache::has($key));
    }

    public function test_malformed_discovery_json_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            new Response(200, [], 'this is not json'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to get the OIDC configuration');

        $this->openIdConfigOf($provider);
    }

    public function test_a_null_discovery_document_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            new Response(200, ['Content-Type' => 'application/json'], 'null'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a JSON object');

        $this->openIdConfigOf($provider);
    }

    public function test_an_empty_discovery_document_is_rejected(): void
    {
        $provider = $this->makeProvider([], [
            new Response(200, ['Content-Type' => 'application/json'], '[]'),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not a JSON object');

        $this->openIdConfigOf($provider);
    }

    public function test_discovery_http_failure_is_rejected(): void
    {
        $provider = $this->makeProvider([], [new Response(500, [], 'oops')]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to get the OIDC configuration');

        $this->openIdConfigOf($provider);
    }

    public function test_discovery_failure_is_not_cached(): void
    {
        $provider = $this->makeProvider([], [new Response(500, [], 'oops')]);

        try {
            $this->openIdConfigOf($provider);
            $this->fail('Expected discovery to fail.');
        } catch (InvalidArgumentException) {
            // expected
        }

        $key = 'openidconnect_discovery_'.md5(static::$opBaseUrl.'/.well-known/openid-configuration');
        $this->assertFalse(Cache::has($key));

        // A later attempt succeeds once the OP recovers.
        $retry = $this->makeProvider([], [$this->jsonResponse($this->discoveryDocument())]);
        $this->assertSame(static::$opBaseUrl.'/token', $this->openIdConfigOf($retry)['token_endpoint']);
    }
}
