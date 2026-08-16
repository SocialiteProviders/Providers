<?php

namespace SocialiteProviders\Tests\OpenIDConnect;

use Firebase\JWT\JWT;
use Orchestra\Testbench\TestCase as Orchestra;
use SocialiteProviders\Manager\ServiceProvider;
use SocialiteProviders\OpenIDConnect\OpenIDConnectServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
            OpenIDConnectServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        // php-jwt's leeway is global mutable state the provider writes to.
        JWT::$leeway = 0;
    }
}
