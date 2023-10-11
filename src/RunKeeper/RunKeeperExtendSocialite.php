<?php

namespace SocialiteProviders\RunKeeper;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RunKeeperExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('runkeeper', Provider::class);
    }
}
