<?php

namespace SocialiteProviders\UFS;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UFSExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ufs', Provider::class);
    }
}
