<?php

namespace SocialiteProviders\Auth0;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Auth0ExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('auth0', Provider::class);
    }
}
