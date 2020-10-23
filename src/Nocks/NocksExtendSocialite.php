<?php

namespace SocialiteProviders\Nocks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NocksExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('nocks', Provider::class);
    }
}
