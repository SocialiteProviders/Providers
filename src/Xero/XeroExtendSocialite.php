<?php

namespace SocialiteProviders\Xero;

use SocialiteProviders\Manager\SocialiteWasCalled;

class XeroExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('xero', Provider::class);
    }
}
