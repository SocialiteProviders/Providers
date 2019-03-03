<?php

namespace SocialiteProviders\GarminConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GarminConnectExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'garmin-connect',
            __NAMESPACE__.'\Provider',
            __NAMESPACE__.'\Server'
        );
    }
}
