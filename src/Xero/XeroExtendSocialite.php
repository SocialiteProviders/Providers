<?php

namespace SocialiteProviders\Xero;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XeroExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('xero', Provider::class);
    }
}
