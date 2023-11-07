<?php

namespace SocialiteProviders\WeChatWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatWebExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('wechat_web', Provider::class);
    }
}
