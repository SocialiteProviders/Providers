<?php

namespace SocialiteProviders\StartGg;

use SocialiteProviders\Manager\SocialiteWasCalled;

class StartGgExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('startgg', Provider::class);
    }
}
