<?php

namespace SocialiteProviders\Logto;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LogtoExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('logto', Provider::class);
    }
}
