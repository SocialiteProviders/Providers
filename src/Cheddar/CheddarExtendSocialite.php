<?php

namespace SocialiteProviders\Cheddar;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CheddarExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('cheddar', Provider::class);
    }
}
