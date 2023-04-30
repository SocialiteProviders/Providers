<?php

namespace SocialiteProviders\LifeScienceLogin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LifeScienceLoginExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('lifesciencelogin', Provider::class);
    }
}
