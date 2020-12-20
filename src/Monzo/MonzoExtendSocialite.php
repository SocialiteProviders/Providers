<?php

namespace SocialiteProviders\Monzo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class MonzoExtendSocialite
{
    /**
     * Register the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('monzo', Provider::class);
    }
}
