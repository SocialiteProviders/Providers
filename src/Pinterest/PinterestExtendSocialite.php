<?php

namespace SocialiteProviders\Pinterest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PinterestExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pinterest', Provider::class);
    }
}
