<?php

namespace SocialiteProviders\Teamleader;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamleaderExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamleader', __NAMESPACE__.'\Provider');
    }
}
