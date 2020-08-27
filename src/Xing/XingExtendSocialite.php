<?php

namespace SocialiteProviders\Xing;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XingExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('xing', Provider::class, Server::class);
    }
}
