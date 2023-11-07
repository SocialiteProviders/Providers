<?php

namespace SocialiteProviders\Nocks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NocksExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('nocks', Provider::class);
    }
}
