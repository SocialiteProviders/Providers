<?php

namespace SocialiteProviders\Weibo;

use SocialiteProviders\Manager\SocialiteWasCalled;

class WeiboExtendSocialite
{
    public function handle(SocialiteWasCalled $socialiteWasCalled): void
    {
        $socialiteWasCalled->extendSocialite('weibo', Provider::class);
    }
}
