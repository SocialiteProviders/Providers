<?php

namespace SocialiteProviders\Weibo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeiboExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('weibo', Provider::class);
    }
}
