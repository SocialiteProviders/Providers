<?php

namespace SocialiteProviders\Zalo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class ZaloExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('zalo', Provider::class);
    }
}
