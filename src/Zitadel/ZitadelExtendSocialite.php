<?php

namespace SocialiteProviders\Zitadel;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZitadelExtendSocialite
{

    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zitadel', Provider::class);
    }
}
