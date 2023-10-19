<?php

namespace SocialiteProviders\Strava;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StravaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('strava', Provider::class);
    }
}
