<?php

namespace SocialiteProviders\Tests\Azure;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use SocialiteProviders\Azure\Provider;
use SocialiteProviders\Manager\Config;
use SocialiteProviders\Tests\TestCase;

class AzureProviderTest extends TestCase
{
    protected function provider(): string
    {
        return Provider::class;
    }

    public function test_graph_url_can_be_configured(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ]);

        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['graph_url' => 'https://graph.example.test/v1.0/me']
        ));
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $user = $provider->userFromToken('access-token');

        $this->assertSame('azure-user-id', $user->getId());
        $this->assertSame('https://graph.example.test/v1.0/me', (string) $mock->getLastRequest()?->getUri());
    }

    public function test_empty_graph_url_uses_default(): void
    {
        $mock = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'], $this->fixture('user.json')),
        ]);

        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            ['graph_url' => '']
        ));
        $provider->setHttpClient(new Client(['handler' => HandlerStack::create($mock)]));

        $user = $provider->userFromToken('access-token');

        $this->assertSame('azure-user-id', $user->getId());
        $this->assertSame('https://graph.microsoft.com/v1.0/me', (string) $mock->getLastRequest()?->getUri());
    }

    public function test_base_url_can_be_configured(): void
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            [
                'base_url' => 'https://login.example.test',
                'tenant'   => 'tenant-id',
            ]
        ));

        $this->assertStringStartsWith(
            'https://login.example.test/tenant-id/oauth2/v2.0/authorize',
            $provider->stateless()->redirect()->getTargetUrl()
        );
    }

    public function test_empty_base_url_uses_default(): void
    {
        /** @var Provider $provider */
        $provider = $this->makeProvider();
        $provider->setConfig(new Config(
            static::CLIENT_ID,
            static::CLIENT_SECRET,
            static::REDIRECT_URI,
            [
                'base_url' => '',
                'tenant'   => 'tenant-id',
            ]
        ));

        $this->assertStringStartsWith(
            'https://login.microsoftonline.com/tenant-id/oauth2/v2.0/authorize',
            $provider->stateless()->redirect()->getTargetUrl()
        );
    }

    public function test_graph_url_is_an_additional_config_key(): void
    {
        $this->assertContains('base_url', Provider::additionalConfigKeys());
        $this->assertContains('graph_url', Provider::additionalConfigKeys());
    }
}
