<?php

namespace SocialiteProviders\ProductHunt;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ProductHuntExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('producthunt', Provider::class);
    }
}
