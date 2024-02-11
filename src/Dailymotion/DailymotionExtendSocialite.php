<?php

namespace SocialiteProviders\Dailymotion;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DailymotionExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('dailymotion', Provider::class);
    }
}
