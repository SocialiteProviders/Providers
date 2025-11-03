<?php

namespace SocialiteProviders\Redbooth;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RedBoothExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('redbooth', Provider::class);
    }
}
