<?php

namespace SocialiteProviders\Teamweek;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamweekExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamweek', Provider::class);
    }
}
