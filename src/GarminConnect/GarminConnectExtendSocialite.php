<?php

namespace SocialiteProviders\GarminConnect;

use SocialiteProviders\Manager\SocialiteWasCalled;

class GarminConnectExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('garmin-connect', Provider::class, Server::class);
    }
}
