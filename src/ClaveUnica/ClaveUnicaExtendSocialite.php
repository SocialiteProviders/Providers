<?php

namespace SocialiteProviders\ClaveUnica;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ClaveUnicaExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('claveunica', __NAMESPACE__.'\Provider');
    }
}
