<?php

namespace SocialiteProviders\Auth0;

use SocialiteProviders\Manager\SocialiteWasCalled;

class Auth0ExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('auth0', Provider::class);
    }
}
