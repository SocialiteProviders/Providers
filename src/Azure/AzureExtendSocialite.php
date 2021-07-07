<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('azure', Provider::class);
    }
}
