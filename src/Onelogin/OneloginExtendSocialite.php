<?php

namespace SocialiteProviders\Onelogin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OneloginExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('onelogin', Provider::class);
    }
}
