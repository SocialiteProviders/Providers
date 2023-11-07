<?php

namespace SocialiteProviders\LifeScienceLogin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LifeScienceLoginExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('lifesciencelogin', Provider::class);
    }
}
