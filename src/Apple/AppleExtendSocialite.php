<?php

namespace SocialiteProviders\Apple;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AppleExtendSocialite
{
    /**
     * Register the Provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'apple',
            __NAMESPACE__.'\Provider'
        );
    }
}
