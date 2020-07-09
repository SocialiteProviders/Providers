<?php

namespace SocialiteProviders\Zoho;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZohoExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zoho', Provider::class);
    }
}
