<?php

namespace SocialiteProviders\WeChatServiceAccount;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatServiceAccountExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('wechat_service_account', Provider::class);
    }
}
