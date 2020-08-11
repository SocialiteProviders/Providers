<?php

namespace SocialiteProviders\Auth0;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Auth0ExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('auth0', Provider::class);
    }
}
