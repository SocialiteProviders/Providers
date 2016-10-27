<?php

namespace SocialiteProviders\RunKeeper;

use SocialiteProviders\Manager\SocialiteWasCalled;

class RunKeeperExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'runkeeper', __NAMESPACE__.'\Provider'
        );
    }
}
