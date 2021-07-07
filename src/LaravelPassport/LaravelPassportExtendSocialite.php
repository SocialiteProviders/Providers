<?php

namespace SocialiteProviders\LaravelPassport;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LaravelPassportExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('laravelpassport', Provider::class);
    }
}
