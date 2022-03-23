<?php

namespace SocialiteProviders\Steam;

use SocialiteProviders\Manager\SocialiteWasCalled;

class SteamExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('steam', Provider::class);
    }
}
