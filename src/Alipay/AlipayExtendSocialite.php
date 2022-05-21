<?php

namespace SocialiteProviders\Alipay;

use SocialiteProviders\Manager\SocialiteWasCalled;

class AlipayExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('alipay', Provider::class);
    }
}
