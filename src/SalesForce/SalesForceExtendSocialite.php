<?php

namespace SocialiteProviders\SalesForce;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SalesForceExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite(
            Provider::PROVIDER_NAME,
            Provider::class
        );
    }
}
