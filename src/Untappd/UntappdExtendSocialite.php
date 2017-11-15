<?php

namespace SocialiteProviders\Untappd;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UntappdExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'untappd', __NAMESPACE__.'\Provider'
        );
    }
}
