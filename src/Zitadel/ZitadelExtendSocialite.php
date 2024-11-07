<?php

namespace SocialiteProviders\Zitadel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZitadelExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('zitadel', Provider::class);
    }
}
