<?php

namespace SocialiteProviders\Saml2\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SocialiteProviders\Manager\ServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function defineRoutes($router): void
    {
        $router->get('auth/callback', fn () => 'ok')->name('saml2.callback.get');
        $router->post('auth/callback', fn () => 'ok')->name('saml2.callback.post');
        $router->get('auth/saml2/logout', fn () => 'ok')->name('saml2.logout');
    }

    protected function getPackageProviders($app): array
    {
        return [
            ServiceProvider::class,
        ];
    }
}
