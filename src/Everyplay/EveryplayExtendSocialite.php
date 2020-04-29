<?php

namespace SocialiteProviders\Everyplay;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EveryplayExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'everyplay',
            __NAMESPACE__.'\Provider'
        );
    }
}
