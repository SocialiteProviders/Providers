<?php

namespace SocialiteProviders\HarID;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HarIDExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('harid', Provider::class);
    }
}
