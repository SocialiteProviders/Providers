<?php

namespace SocialiteProviders\HarID;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HarIDExtendSocialite
{
    /**
     * Register the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('harid', Provider::class);
    }
}
