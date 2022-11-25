<?php

namespace SocialiteProviders\Huawei;

use SocialiteProviders\Manager\SocialiteWasCalled;

class HuaweiExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('huawei', Provider::class);
    }
}
