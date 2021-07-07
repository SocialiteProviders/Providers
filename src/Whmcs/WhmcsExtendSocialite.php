<?php

namespace SocialiteProviders\Whmcs;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WhmcsExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('whmcs', Provider::class);
    }
}
