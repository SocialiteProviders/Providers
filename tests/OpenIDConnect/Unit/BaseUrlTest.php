<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\Support\InteractsWithOidc;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class BaseUrlTest extends TestCase
{
    use InteractsWithOidc;

    private function baseUrlOf(Provider $provider): string
    {
        return (new ReflectionMethod($provider, 'getBaseUrl'))->invoke($provider);
    }

    public function test_https_base_url_is_accepted_and_trailing_slash_trimmed(): void
    {
        $provider = $this->makeProvider(['base_url' => 'https://id.example.com/realms/main/']);

        $this->assertSame('https://id.example.com/realms/main', $this->baseUrlOf($provider));
    }

    public function test_missing_base_url_is_rejected(): void
    {
        $provider = $this->makeProvider(['base_url' => null]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('base_url is not configured');

        $this->baseUrlOf($provider);
    }

    public function test_plain_http_base_url_is_rejected(): void
    {
        $provider = $this->makeProvider(['base_url' => 'http://id.example.com']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must use https');

        $this->baseUrlOf($provider);
    }

    public function test_schemeless_base_url_is_rejected(): void
    {
        $provider = $this->makeProvider(['base_url' => 'id.example.com']);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must use https');

        $this->baseUrlOf($provider);
    }

    #[DataProvider('loopbackHosts')]
    public function test_plain_http_is_allowed_for_loopback_hosts(string $baseUrl): void
    {
        $provider = $this->makeProvider(['base_url' => $baseUrl]);

        $this->assertSame($baseUrl, $this->baseUrlOf($provider));
    }

    public static function loopbackHosts(): array
    {
        return [
            'localhost'      => ['http://localhost:8080'],
            'ipv4 loopback'  => ['http://127.0.0.1:8080'],
            'ipv6 loopback'  => ['http://[::1]:8080'],
            'localhost tld'  => ['http://keycloak.localhost'],
        ];
    }

    public function test_lookalike_host_is_not_treated_as_loopback(): void
    {
        $provider = $this->makeProvider(['base_url' => 'http://localhost.evil.com']);

        $this->expectException(InvalidArgumentException::class);

        $this->baseUrlOf($provider);
    }
}
