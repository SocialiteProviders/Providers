<?php

namespace SocialiteProviders\WeChatServiceAccount;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatServiceAccountExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wechat_service_account', Provider::class);
    }
}
