<?php

namespace SocialiteProviders\StartGg;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StartGgExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('startgg', Provider::class);
    }
}
