<?php

namespace SocialiteProviders\Zalo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZaloExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zalo', Provider::class);
    }
}
