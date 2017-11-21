<?php

namespace SocialiteProviders\Okta;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OktaExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('okta', __NAMESPACE__.'\Provider');
    }
}
