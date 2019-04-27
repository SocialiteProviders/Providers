<?php

namespace SocialiteProviders\WeChatWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatWebExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wechat_web', __NAMESPACE__.'\Provider');
    }
}
