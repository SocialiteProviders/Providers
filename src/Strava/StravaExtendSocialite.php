<?php

namespace SocialiteProviders\Strava;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StravaExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('strava', Provider::class);
    }
}
