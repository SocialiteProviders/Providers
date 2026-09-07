<?php

namespace SocialiteProviders\Vipps;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VippsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('vipps', Provider::class);
    }
}
