<?php

namespace SocialiteProviders\TeamService;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamServiceExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamservice', __NAMESPACE__.'\Provider');
    }
}
