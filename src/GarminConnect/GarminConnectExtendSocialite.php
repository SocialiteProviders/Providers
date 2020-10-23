<?php

namespace SocialiteProviders\GarminConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GarminConnectExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('garmin-connect', Provider::class, Server::class);
    }
}
