<?php

namespace SocialiteProviders\Live;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LiveExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('live', Provider::class);
    }
}
