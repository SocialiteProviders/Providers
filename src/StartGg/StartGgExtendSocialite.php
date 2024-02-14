<?php

namespace SocialiteProviders\StartGg;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StartGgExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('startgg', Provider::class);
    }
}
