<?php

namespace SocialiteProviders\Okta;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OktaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('okta', Provider::class);
    }
}
