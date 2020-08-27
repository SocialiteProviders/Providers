<?php

namespace SocialiteProviders\AppNet;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AppNetExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('appnet', Provider::class);
    }
}
