<?php

namespace SocialiteProviders\HubSpot;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HubSpotExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('hubspot', Provider::class);
    }
}
