<?php

namespace SocialiteProviders\PeeringDB;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PeeringDBExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('peeringdb', __NAMESPACE__.'\Provider');
    }
}
