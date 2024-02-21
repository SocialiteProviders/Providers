<?php

namespace SocialiteProviders\Clover;

use SocialiteProviders\Manager\SocialiteWasCalled;

class CloverExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('clover', Provider::class);
    }
}
