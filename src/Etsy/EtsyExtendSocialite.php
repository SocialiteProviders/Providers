<?php

namespace SocialiteProviders\Etsy;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EtsyExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('etsy', Provider::class, Server::class);
    }
}
