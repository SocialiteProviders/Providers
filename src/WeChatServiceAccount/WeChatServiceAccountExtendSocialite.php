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
        $socialiteWasCalled->extendSocialite('we_chat_service_account', __NAMESPACE__.'\Provider');
    }
}
