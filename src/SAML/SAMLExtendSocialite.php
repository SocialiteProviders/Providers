<?php

namespace SocialiteProviders\SAML;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SAMLExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('saml', Provider::class);
    }
}
