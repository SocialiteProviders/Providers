<?php

namespace SocialiteProviders\Yammer;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YammerExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('yammer', Provider::class);
    }
}
