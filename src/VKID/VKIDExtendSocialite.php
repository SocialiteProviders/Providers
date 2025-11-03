<?php

namespace SocialiteProviders\VKID;

use SocialiteProviders\Manager\SocialiteWasCalled;

class VKIDExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('vkid', Provider::class);
    }
}
