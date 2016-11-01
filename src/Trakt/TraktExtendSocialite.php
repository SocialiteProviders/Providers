<?php

namespace SocialiteProviders\Trakt;

use SocialiteProviders\Manager\SocialiteWasCalled;

class TraktExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('trakt', __NAMESPACE__.'\Provider');
    }
}
