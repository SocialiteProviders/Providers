<?php

namespace SocialiteProviders\Fitbit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FitbitExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('fitbit', Provider::class);
    }
}
