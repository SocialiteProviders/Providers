<?php

namespace SocialiteProviders\TeamService;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TeamServiceExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('teamservice', Provider::class);
    }
}
