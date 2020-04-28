<?php

namespace SocialiteProviders\Ufutx;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UfutxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'ufutx',
            __NAMESPACE__.'\Provider'
        );
    }
}
