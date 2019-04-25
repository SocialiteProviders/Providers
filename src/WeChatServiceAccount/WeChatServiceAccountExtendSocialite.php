<?php

namespace SocialiteProviders\WeChatServiceAccount;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatServiceAccountExtendSocialite
{
    /**
     * Execute the provider.
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wechat_service_account', __NAMESPACE__.'\Provider');
    }
}
