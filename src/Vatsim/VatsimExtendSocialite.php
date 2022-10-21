<?php

namespace SocialiteProviders\Vatsim;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VatsimExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vatsim', Provider::class);
    }
}
