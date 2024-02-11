<?php

namespace SocialiteProviders\IFSP;

use SocialiteProviders\Manager\SocialiteWasCalled;

class IFSPExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ifsp', Provider::class);
    }
}
