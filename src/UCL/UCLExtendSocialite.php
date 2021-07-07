<?php

namespace SocialiteProviders\UCL;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UCLExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('uclapi', Provider::class);
    }
}
