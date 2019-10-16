<?php

namespace SocialiteProviders\Zeit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZeitExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zeit', __NAMESPACE__.'\Provider');
    }
}
