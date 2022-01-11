<?php

namespace SocialiteProviders\SalesForceSandbox;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SalesForceSandboxExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            Provider::PROVIDER_NAME,
            Provider::class
        );
    }
}
