<?php

namespace SocialiteProviders\Azure;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AzureExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'azure',
            __NAMESPACE__.'\Provider'
        );
    }
}
