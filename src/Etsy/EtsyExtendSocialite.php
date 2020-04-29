<?php

namespace SocialiteProviders\Etsy;

use SocialiteProviders\Manager\SocialiteWasCalled;

class EtsyExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'etsy',
            __NAMESPACE__.'\Provider',
            __NAMESPACE__.'\Server'
        );
    }
}
