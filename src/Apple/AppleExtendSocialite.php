<?php

namespace SocialiteProviders\Apple;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AppleExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('apple', Provider::class);
    }
}
