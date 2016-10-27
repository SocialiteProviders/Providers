<?php

namespace SocialiteProviders\Venmo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VenmoExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('venmo', __NAMESPACE__.'\Provider');
    }
}
