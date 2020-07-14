<?php

namespace SocialiteProviders\Etsy;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EtsyExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('etsy', Provider::class, Server::class);
    }
}
