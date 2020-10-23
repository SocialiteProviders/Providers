<?php

namespace SocialiteProviders\Fitbit;

use SocialiteProviders\Manager\SocialiteWasCalled;

class FitbitExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('fitbit', Provider::class);
    }
}
