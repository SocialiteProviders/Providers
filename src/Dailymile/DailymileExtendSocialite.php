<?php

namespace SocialiteProviders\Dailymile;

use SocialiteProviders\Manager\SocialiteWasCalled;

class DailymileExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'dailymile', __NAMESPACE__.'\Provider'
        );
    }
}
