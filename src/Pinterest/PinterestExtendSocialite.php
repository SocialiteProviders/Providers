<?php

namespace SocialiteProviders\Pinterest;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PinterestExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('pinterest', Provider::class);
    }
}
