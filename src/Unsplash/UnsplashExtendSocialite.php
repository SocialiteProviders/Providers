<?php

namespace SocialiteProviders\Unsplash;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UnsplashExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('unsplash', Provider::class);
    }
}
