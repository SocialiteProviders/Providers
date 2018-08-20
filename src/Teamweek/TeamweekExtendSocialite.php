<?php

namespace SocialiteProviders\Teamweek;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamweekExtendSocialite
{
    /**
     * Execute the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     *
     * @return void
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamweek', __NAMESPACE__.'\Provider');
    }
}
