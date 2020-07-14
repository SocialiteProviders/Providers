<?php

namespace SocialiteProviders\UCL;

use SocialiteProviders\Manager\SocialiteWasCalled;

class UCLExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('uclapi', Provider::class);
    }
}
