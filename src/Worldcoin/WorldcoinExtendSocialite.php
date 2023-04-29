<?php

namespace SocialiteProviders\Worldcoin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WorldcoinExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('worldcoin', Provider::class);
    }
}
