<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Unit;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use ReflectionMethod;
use ReflectionProperty;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class HttpClientTest extends TestCase
{
    private function clientOptions(array $config, array $guzzle = []): array
    {
        $provider = new Provider(
            Request::create('https://app.test/login'),
            'the-client',
            'the-secret',
            'https://app.test/callback',
            $guzzle,
        );

        $provider->setConfig(new Config('the-client', 'the-secret', 'https://app.test/callback', $config));

        $client = (new ReflectionMethod($provider, 'getHttpClient'))->invoke($provider);
        $this->assertInstanceOf(Client::class, $client);

        return (new ReflectionProperty($client, 'config'))->getValue($client);
    }

    public function test_proxy_config_is_passed_to_the_http_client(): void
    {
        $options = $this->clientOptions([
            'base_url' => 'https://op.test',
            'proxy'    => 'http://proxy.corp.test:8080',
        ]);

        $this->assertSame('http://proxy.corp.test:8080', $options['proxy']);
    }

    public function test_no_proxy_is_set_by_default(): void
    {
        $options = $this->clientOptions(['base_url' => 'https://op.test']);

        $this->assertArrayNotHasKey('proxy', $options);
    }

    public function test_timeouts_fall_back_to_the_defaults(): void
    {
        $options = $this->clientOptions(['base_url' => 'https://op.test']);

        $this->assertSame(5.0, $options['connect_timeout']);
        $this->assertSame(10.0, $options['timeout']);
    }

    public function test_an_explicit_zero_timeout_disables_the_timeout(): void
    {
        $options = $this->clientOptions([
            'base_url'             => 'https://op.test',
            'http_connect_timeout' => 0,
            'http_timeout'         => 0,
        ]);

        $this->assertSame(0.0, $options['connect_timeout']);
        $this->assertSame(0.0, $options['timeout']);
    }

    public function test_an_empty_timeout_falls_back_to_the_default(): void
    {
        $options = $this->clientOptions([
            'base_url'             => 'https://op.test',
            'http_connect_timeout' => '',
            'http_timeout'         => '',
        ]);

        $this->assertSame(5.0, $options['connect_timeout']);
        $this->assertSame(10.0, $options['timeout']);
    }

    public function test_socialite_guzzle_options_reach_the_http_client(): void
    {
        $options = $this->clientOptions(
            ['base_url' => 'https://op.test'],
            ['headers' => ['X-Relay-Token' => 'relay-secret'], 'verify' => '/etc/ssl/corp-ca.pem'],
        );

        $this->assertSame('relay-secret', $options['headers']['X-Relay-Token']);
        $this->assertSame('/etc/ssl/corp-ca.pem', $options['verify']);
    }

    public function test_guzzle_timeouts_apply_when_the_dedicated_keys_are_absent(): void
    {
        $options = $this->clientOptions(['base_url' => 'https://op.test'], ['timeout' => 30, 'connect_timeout' => 3]);

        $this->assertSame(3.0, $options['connect_timeout']);
        $this->assertSame(30.0, $options['timeout']);
    }

    public function test_the_dedicated_keys_win_over_guzzle_options(): void
    {
        $options = $this->clientOptions(
            [
                'base_url'             => 'https://op.test',
                'http_connect_timeout' => 1,
                'http_timeout'         => 2,
                'proxy'                => 'http://proxy.corp.test:8080',
            ],
            ['connect_timeout' => 3, 'timeout' => 30, 'proxy' => 'http://other.test:3128'],
        );

        $this->assertSame(1.0, $options['connect_timeout']);
        $this->assertSame(2.0, $options['timeout']);
        $this->assertSame('http://proxy.corp.test:8080', $options['proxy']);
    }

    public function test_proxy_is_a_declared_config_key(): void
    {
        $this->assertContains('proxy', Provider::additionalConfigKeys());
    }
}
