<?php

namespace SocialiteProviders\Yahoo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class YahooExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('yahoo', __NAMESPACE__.'\Provider');
    }
}
