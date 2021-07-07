<?php

namespace SocialiteProviders\WeChatWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeChatWebExtendSocialite
{
    /**
     * Register the provider.
     *
     * @param \SocialiteProviders\Manager\SocialiteWasCalled $socialiteWasCalled
     */
    public function handle(SocialiteWasCalled $socialiteWasCalled)
    {
        $socialiteWasCalled->extendSocialite('wechat_web', Provider::class);
    }
}
