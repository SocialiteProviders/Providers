<?php

namespace SocialiteProviders\LaravelPassport;

use SocialiteProviders\Manager\SocialiteWasCalled;

class LaravelPassportExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('laravelpassport', Provider::class);
    }
}
