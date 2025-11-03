<?php

namespace SocialiteProviders\Toyhouse;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ToyhouseExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('toyhouse', Provider::class);
    }
}
