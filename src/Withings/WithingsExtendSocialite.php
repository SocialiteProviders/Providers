<?php

namespace SocialiteProviders\Withings;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WithingsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'withings',
            __NAMESPACE__.'\Provider',
            __NAMESPACE__.'\Server'
        );
    }
}
