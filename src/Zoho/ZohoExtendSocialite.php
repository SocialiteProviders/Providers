<?php

namespace SocialiteProviders\Zoho;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZohoExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zoho', Provider::class);
    }
}
