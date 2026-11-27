<?php

namespace SocialiteProviders\Stalwart;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StalwartExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('stalwart', Provider::class);
    }
}
