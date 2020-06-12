<?php

namespace SocialiteProviders\Sage;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SageExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('sage', __NAMESPACE__.'\Provider');
    }
}
