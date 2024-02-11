<?php

namespace SocialiteProviders\UCL;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UCLExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('uclapi', Provider::class);
    }
}
