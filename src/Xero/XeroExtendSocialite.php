<?php

namespace SocialiteProviders\Xero;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XeroExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('xero', Provider::class);
    }
}
