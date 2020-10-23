<?php

namespace SocialiteProviders\Pinterest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PinterestExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('pinterest', Provider::class);
    }
}
