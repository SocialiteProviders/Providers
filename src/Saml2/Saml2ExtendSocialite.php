<?php

namespace SocialiteProviders\Saml2;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Saml2ExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('saml2', Provider::class);
    }
}
