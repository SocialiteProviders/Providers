<?php

namespace SocialiteProviders\Uber;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UberExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('uber', Provider::class);
    }
}
