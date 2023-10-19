<?php

namespace SocialiteProviders\Toyhouse;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ToyhouseExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param  \SocialiteProviders\Manager\SocialiteWasCalled  $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('toyhouse', Provider::class);
    }
}
