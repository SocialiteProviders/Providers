<?php

namespace SocialiteProviders\Steady;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SteadyExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('steady', Provider::class);
    }
}
