<?php

namespace SocialiteProviders\OnlineScoutManager;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OnlineScoutManagerExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('osm', Provider::class);
    }
}
