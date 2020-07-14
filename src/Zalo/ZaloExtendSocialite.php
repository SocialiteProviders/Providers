<?php

namespace SocialiteProviders\Zalo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZaloExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zalo', Provider::class);
    }
}
