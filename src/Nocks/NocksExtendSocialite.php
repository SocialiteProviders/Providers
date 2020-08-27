<?php

namespace SocialiteProviders\Nocks;

use SocialiteProviders\Manager\SocialiteWasCalled;

class NocksExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('nocks', Provider::class);
    }
}
