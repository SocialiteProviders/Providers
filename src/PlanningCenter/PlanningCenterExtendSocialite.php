<?php

namespace SocialiteProviders\PlanningCenter;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PlanningCenterExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('planningcenter', Provider::class);
    }
}
