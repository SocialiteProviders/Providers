<?php

namespace SocialiteProviders\Harvest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HarvestExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('harvest', Provider::class);
    }
}
