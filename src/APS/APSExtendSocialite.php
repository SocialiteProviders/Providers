<?php

namespace SocialiteProviders\APS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class APSExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('aps', Provider::class);
    }
}
