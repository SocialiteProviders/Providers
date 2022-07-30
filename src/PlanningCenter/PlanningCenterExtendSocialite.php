<?php

namespace SocialiteProviders\PlanningCenter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PlanningCenterExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('planningcenter', Provider::class);
    }
}
