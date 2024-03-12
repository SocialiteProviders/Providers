<?php

namespace SocialiteProviders\Withings;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WithingsExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('withings', Provider::class, Server::class);
    }
}
