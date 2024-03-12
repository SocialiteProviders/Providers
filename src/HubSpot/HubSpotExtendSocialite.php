<?php

namespace SocialiteProviders\HubSpot;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HubSpotExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('hubspot', Provider::class);
    }
}
