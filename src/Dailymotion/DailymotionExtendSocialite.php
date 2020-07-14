<?php

namespace SocialiteProviders\Dailymotion;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DailymotionExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('dailymotion', Provider::class);
    }
}
