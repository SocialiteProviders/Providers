<?php

namespace SocialiteProviders\Weixin;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'weixin', __NAMESPACE__.'\Provider'
        );
    }
}
