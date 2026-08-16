<?php

namespace SocialiteProviders\Tests\OpenIDConnect\Feature;

use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\OpenIDConnect\Provider;
use SocialiteProviders\Tests\OpenIDConnect\TestCase;

class DriverPrefixTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('oidc.driver_prefix', 'sso_');
        $app['config']->set('oidc.connections', [
            'main' => [
                'base_url'      => 'https://sso.test',
                'client_id'     => 'main-client',
                'client_secret' => 'main-secret',
                'redirect'      => 'https://app.test/callback',
            ],
        ]);
    }

    public function test_the_driver_prefix_is_configurable(): void
    {
        $this->assertInstanceOf(Provider::class, Socialite::driver('sso_main'));
        $this->assertSame('main-client', config('services.sso_main.client_id'));
    }

    public function test_the_default_prefix_is_not_used_when_overridden(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        Socialite::driver('oidc_main');
    }
}
