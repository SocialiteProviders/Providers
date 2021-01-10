<?php

namespace SocialiteProviders\WeixinWork;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinWorkExtendSocialite
{

    

    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('weixinwork', Provider::class);
    }
}
