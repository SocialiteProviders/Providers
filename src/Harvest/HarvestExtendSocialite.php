<?php

namespace SocialiteProviders\Harvest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HarvestExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('harvest', Provider::class);
    }
}
