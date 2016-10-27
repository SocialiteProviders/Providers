<?php

namespace SocialiteProviders\WeixinWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinWebExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite(
            'weixinweb', __NAMESPACE__.'\Provider'
        );
    }
}
