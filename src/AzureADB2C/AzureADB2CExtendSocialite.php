<?php

namespace SocialiteProviders\AzureADB2C;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureADB2CExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('azureadb2c', Provider::class);
    }
}
