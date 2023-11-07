<?php

namespace SocialiteProviders\WeixinWeb;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeixinWebExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('weixinweb', Provider::class);
    }
}
