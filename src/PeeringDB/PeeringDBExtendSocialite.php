<?php

namespace SocialiteProviders\PeeringDB;

use SocialiteProviders\Manager\SocialiteWasCalled;

class PeeringDBExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('peeringdb', Provider::class);
    }
}
