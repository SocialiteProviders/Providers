<?php

namespace SocialiteProviders\Live;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LiveExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'live', __NAMESPACE__.'\Provider'
        );
    }
}
