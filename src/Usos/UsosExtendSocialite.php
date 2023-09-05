<?php

namespace SocialiteProviders\Usos;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UsosExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('usos', Provider::class, Server::class);
    }
}
