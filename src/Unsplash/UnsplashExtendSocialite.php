<?php

namespace SocialiteProviders\Unsplash;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UnsplashExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'unsplash',
            __NAMESPACE__.'\Provider'
        );
    }
}
