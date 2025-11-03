<?php

namespace SocialiteProviders\Ufutx;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UfutxExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('ufutx', Provider::class);
    }
}
