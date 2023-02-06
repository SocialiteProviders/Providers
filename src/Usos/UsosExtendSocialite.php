<?php

namespace SocialiteProviders\Usos;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UsosExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('usos', Provider::class, Server::class);
    }
}
