<?php

namespace SocialiteProviders\OSChina;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OSChinaExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('oschina', Provider::class);
    }
}
