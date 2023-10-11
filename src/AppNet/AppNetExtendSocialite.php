<?php

namespace SocialiteProviders\AppNet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AppNetExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('appnet', Provider::class);
    }
}
