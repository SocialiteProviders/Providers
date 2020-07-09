<?php

namespace SocialiteProviders\OSChina;

use SocialiteProviders\Manager\SocialiteWasCalled;

class OSChinaExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('oschina', Provider::class);
    }
}
