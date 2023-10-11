<?php

namespace SocialiteProviders\Yiban;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YibanExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yiban', Provider::class);
    }
}
