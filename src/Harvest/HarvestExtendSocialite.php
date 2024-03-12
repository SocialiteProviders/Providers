<?php

namespace SocialiteProviders\Harvest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HarvestExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('harvest', Provider::class);
    }
}
