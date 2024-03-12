<?php

namespace SocialiteProviders\Whmcs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WhmcsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('whmcs', Provider::class);
    }
}
