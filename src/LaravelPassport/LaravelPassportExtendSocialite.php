<?php

namespace SocialiteProviders\LaravelPassport;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LaravelPassportExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('laravelpassport', Provider::class);
    }
}
