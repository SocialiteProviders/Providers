<?php

namespace SocialiteProviders\Bexio;

use SocialiteProviders\Manager\SocialiteWasCalled;

class BexioExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('bexio', Provider::class);
    }
}
