<?php

namespace SocialiteProviders\Yiban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YibanExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('yiban', Provider::class);
    }
}
