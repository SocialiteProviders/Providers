<?php

namespace SocialiteProviders\WxWork;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WxWorkExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wxwork', Provider::class);
    }
}
